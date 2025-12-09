<?php

namespace Symbiote\Notifications\Service;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use Symbiote\Notifications\Model\NotifiedOn;
use Symbiote\Notifications\Model\NotificationSender;
use Symbiote\Notifications\Model\SystemNotification;

/**
 * EmailNotificationSender
 *
 * @author  marcus@symbiote.com.au, shea@livesource.co.nz
 * @license http://silverstripe.org/bsd-license/
 */
class EmailNotificationSender implements NotificationSender
{
    use Configurable;
    use Extensible;

    /**
     * Email Address to send email notifications from
     */
    private static string $send_notifications_from = '';

    private static array $dependencies = [
        'logger' => '%$Psr\Log\LoggerInterface',
    ];

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * Send a notification via email to the selected users
     */
    public function sendNotification(SystemNotification $notification, DataObject $context, array $data)
    {
        $users = $notification->getRecipients($context);
        foreach ($users as $user) {
            $this->sendToUser($notification, $context, $user, $data);
        }
    }

    /**
     * Send a notification directly to a single user
     */
    public function sendToUser(SystemNotification $notification, DataObject $context, object $user, array $data)
    {
        $subject = $notification->format($notification->Title, $context, $user, $data);

        $message = $notification->format(
            $notification->NotificationContent(),
            $context,
            $user,
            $data
        );

        if (($template = $notification->getTemplate()) !== '') {
            $templateData = $notification->getTemplateData($context, $user, $data);
            $templateData->setField('Body', $message);
            try {
                $body = $templateData->renderWith($template);
            } catch (\Exception) {
                $body = $message;
            }
        } else {
            $body = $message;
        }

        $from = $this->config()->get('send_notifications_from');
        $to = $user->Email;
        if (!$to && method_exists($user, 'getEmailAddress')) {
            $to = $user->getEmailAddress();
        }

        if (!$from || !$to) {
            return;
        }

        // send
        try {
            $email = Email::create($from, $to, $subject);
            $email->setBody($body);
            $this->extend('onBeforeSendToUser', $email);
            $email->send();
        } catch (\Exception $exception) {
            if ($this->logger) {
                if ($to !== 'admin') {
                    $this->logger->warning("Failed sending email to {$to}");
                }

                $this->logger->warning("sendToUser:" . $exception->getMessage());
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger($logger): self
    {
        $this->logger = $logger;

        return $this;
    }
}

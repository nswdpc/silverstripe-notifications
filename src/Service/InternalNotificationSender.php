<?php

namespace Symbiote\Notifications\Service;

use Exception;
use Symbiote\Notifications\Model\NotificationSender;
use Symbiote\Notifications\Model\SystemNotification;
use Symbiote\Notifications\Model\InternalNotification;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;

/**
 * EmailNotificationSender
 *
 * @author  marcus@symbiote.com.au
 * @license http://silverstripe.org/bsd-license/
 */
class InternalNotificationSender implements NotificationSender
{
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
     * Send a notification directly to a single user (Member)
     * @param object $user this object must be a Member due to it being an internal notification
     */
    public function sendToUser(SystemNotification $notification, DataObject $context, object $user, array $data)
    {
        if (!($user instanceof Member)) {
            // don't send to non-member user object types
            return;
        }

        $subject = $notification->format($notification->Title, $context, $user, $data);

        $content = $notification->NotificationContent();

        if (!Config::inst()->get(SystemNotification::class, 'html_notifications')) {
            $content = strip_tags($content);
        }

        $message = $notification->format(
            $content,
            $context,
            $user,
            $data
        );

        if ($template = $notification->getTemplate()) {
            $templateData = $notification->getTemplateData($context, $user, $data);
            $templateData->setField('Body', $message);
            try {
                $body = $templateData->renderWith($template);
            } catch (Exception) {
                $body = $message;
            }
        } else {
            $body = $message;
        }

        $contextData = array_merge([
            'ClassName' => $context::class,
            'ID' => $context->ID,
            'Link' => $context->hasMethod('Link') ? $context->Link() : ''
        ], $data);

        $currentUser = Security::getCurrentUser();
        $notice = InternalNotification::create([
            'Title' => $subject,
            'Message' => $body,
            'ToID'      => $user->ID,
            'FromID'    => $currentUser ? $currentUser->ID : null,
            'SentOn'    => date('Y-m-d H:i:s'),
            'SourceObjectID' => $context->ID,
            'SourceNotificationID' => $notification->ID,
            'Context' => $contextData
        ]);

        $notice->write();
    }
}

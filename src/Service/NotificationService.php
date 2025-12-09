<?php

namespace Symbiote\Notifications\Service;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\ORM\DataObject;
use Symbiote\Notifications\Job\SendNotificationJob;
use Symbiote\Notifications\Model\NotificationSender;
use Symbiote\Notifications\Model\SystemNotification;
use Symbiote\QueuedJobs\Services\QueuedJobService;

/**
 * NotificationService
 * @author  marcus@symbiote.com.au, shea@livesource.co.nz
 * @license http://silverstripe.org/bsd-license/
 */
class NotificationService
{
    use Configurable;

    /**
     * The default notification send mechanisms to init with
     */
    private static array $default_senders = [
        'email' => EmailNotificationSender::class,
        'internal' => InternalNotificationSender::class,
    ];

    /**
     * The list of channels to send to by default
     */
    private static array $default_channels = [
        'email',
        'internal',
    ];

    /**
     * Should we use the queued jobs approach to sending notifications?
     */
    private static bool $use_queues = true;

    /**
     * The objects to use for actually sending a notification, indexed
     * by their channel ID
     * @var array
     */
    protected $senders;

    /**
     * The list of channels to send to
     * @var array
     */
    protected $channels = [];

    public function __construct()
    {
        if (!class_exists(QueuedJobService::class)) {
            $this->config()->use_queues = false;
        }

        $this->setSenders($this->config()->get('default_senders'));
        $this->setChannels($this->config()->get('default_channels'));
    }

    /**
     * Add a channel that this notification service should use when sending notifications
     * @param string $channel The channel to add
     */
    public function addChannel(string $channel): self
    {
        $this->channels[] = $channel;

        return $this;
    }

    public function getChannels(): array
    {
        return $this->channels;
    }

    /**
     * Set the list of channels this notification service should use when sending notifications
     * @param array $channels The channels to send to
     */
    public function setChannels(array $channels): static
    {
        $this->channels = $channels;

        return $this;
    }

    /**
     * Add a notification sender
     * @param string                    $channel The channel to send through
     * @param NotificationSender|string $sender  The notification channel
     */
    public function addSender(string $channel, NotificationSender|string $sender): self
    {
        $sender = is_string($sender) ? singleton($sender) : $sender;
        $this->senders[$channel] = $sender;

        return $this;
    }

    /**
     * Add a notification sender to a channel
     */
    public function setSenders(array $senders): self
    {
        $this->senders = [];
        foreach ($senders as $channel => $sender) {
            $this->addSender($channel, $sender);
        }

        return $this;
    }

    /**
     * Get a sender for a particular channel
     * @param string $channel
     * @return mixed|null
     */
    public function getSender($channel): mixed
    {
        return $this->senders[$channel] ?? null;
    }

    /**
     * Trigger a notification event
     * @param string      $identifier The Identifier of the notification event
     * @param DataObject  $context    The context (if relevant) of the object to notify on
     * @param array       $data       Extra data to be sent along with the notification
     * @param string $channel a channel to use for this notification, overrides SystemNotification.Channels value
     */
    public function notify(string $identifier, DataObject $context, array $data = [], ?string $channel = null)
    {
        // okay, lets find any notification set up with this identifier
        if ($notifications = SystemNotification::get()->filter('Identifier', $identifier)) {
            foreach ($notifications as $notification) {
                if ($notification->NotifyOnClass) {
                    $subclasses = ClassInfo::subclassesFor($notification->NotifyOnClass);
                    if (!isset($subclasses[strtolower($context::class)])) {
                        continue;
                    }
                }

                // figure out the channels to send the notification on
                $channels = $channel ? [$channel] : [];
                if ($channels === [] && is_string($notification->Channels)) {
                    try {
                        $channels = json_decode($notification->Channels);
                    } catch (\JsonException) {
                        // noop
                    }
                }

                $this->sendNotification($notification, $context, $data, $channels);
            }
        }
    }

    /**
     * Send out a notification
     * @param SystemNotification $notification The configured notification object
     * @param DataObject         $context      The context of the notification to send
     * @param array              $extraData    Any extra data to add into the notification text
     * @param array             $channels     The specific channels to send through. If not set, just
     *                                         sends to the default configured
     */
    public function sendNotification(
        SystemNotification $notification,
        DataObject $context,
        array $extraData = [],
        array $channels = []
    ) {
        // check to make sure that there are users to send it to. If not, we don't bother with it at all
        $recipients = $notification->getRecipients($context);
        if (count($recipients) === 0) {
            return;
        }

        // if we've got queues and a large number of recipients, lets send via a queued job instead
        if (class_exists(QueuedJobService::class) && $this->config()->get('use_queues') && count($recipients) > 5) {
            $extraData['SEND_CHANNELS'] = $channels;
            singleton(QueuedJobService::class)->queueJob(
                new SendNotificationJob(
                    $notification,
                    $context,
                    $extraData
                )
            );
        } else {
            $channels = $channels !== [] ? $channels : $this->getChannels();
            foreach ($channels as $channel) {
                if ($sender = $this->getSender($channel)) {
                    $sender->sendNotification($notification, $context, $extraData);
                }
            }
        }
    }

    /**
     * Sends a notification directly to a user
     * @param object $user
     */
    public function sendToUser(
        SystemNotification $notification,
        DataObject $context,
        object $user,
        array $extraData = []
    ) {
        $channels = $this->channels;
        if ($extraData && isset($extraData['SEND_CHANNELS'])) {
            $channels = $extraData['SEND_CHANNELS'];
            unset($extraData['SEND_CHANNELS']);
        }

        if (!is_array($channels)) {
            $channels = [$channels];
        }

        foreach ($channels as $channel) {
            if ($sender = $this->getSender($channel)) {
                $sender->sendToUser($notification, $context, $user, $extraData);
            }
        }
    }
}

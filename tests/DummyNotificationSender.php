<?php

namespace Symbiote\Notifications\Tests;

use SilverStripe\ORM\DataObject;
use Symbiote\Notifications\Model\NotificationSender;
use Symbiote\Notifications\Model\SystemNotification;

class DummyNotificationSender implements NotificationSender
{
    public $notifications = [];

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
     * Send a notification to a single user at a time
     */
    public function sendToUser(SystemNotification $notification, DataObject $context, object $user, array $data)
    {
        $cls = new \stdClass();
        $cls->notification = $notification;
        $cls->text = $notification->format($notification->NotificationText, $context, $user, $data);
        $cls->context = $context;
        $cls->user = $user;
        $cls->data = $data;

        $this->notifications[] = $cls;
    }
}

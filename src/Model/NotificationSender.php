<?php

declare(strict_types=1);

namespace Symbiote\Notifications\Model;

use SilverStripe\ORM\DataObject;

/**
 * NotificationSender
 *
 * @author  marcus@symbiote.com.au, shea@livesource.co.nz
 * @license http://silverstripe.org/bsd-license/
 */
interface NotificationSender
{
    /**
     * Send a notification.
     * Automatically determines the list of users to send to based on the notification
     * object and context
     */
    public function sendNotification(SystemNotification $notification, DataObject $context, array $data);

    /**
     * Send a notification to a single user at a time
     */
    public function sendToUser(SystemNotification $notification, DataObject $context, object $user, array $data);
}

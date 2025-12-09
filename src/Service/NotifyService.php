<?php

namespace Symbiote\Notifications\Service;

use SilverStripe\ORM\ArrayList;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use Symbiote\Notifications\Extension\MemberExtension;
use Symbiote\Notifications\Model\InternalNotification;

class NotifyService
{
    public function webEnabledMethods(): array
    {
        return [
            'list' => 'GET',
            'read' => 'POST',
            'see' => 'POST'
        ];
    }

    /**
     * List all the notifications a user has, on a particular item,
     * and/or of a particular type
     */
    public function list(): ?ArrayList
    {
        $member = Security::getCurrentUser();
        if (!$member) {
            return null;
        }

        if ($member->hasExtension(MemberExtension::class)) {
            return $member->getNotifications();
        } else {
            return null;
        }
    }

    /**
     * Mark a Notification as read, accepts a notification ID and returns a
     * boolean for success or failure.
     *
     * @param string|int $ID The ID of an InternalNotification for the current
     * logged in Member
     * @return bool true when marked read otherwise false
     */
    public function read($ID): bool
    {
        $member = Security::getCurrentUser();
        if (!$member) {
            return false;
        }

        if ($ID) {
            $notification = InternalNotification::get()
                ->filter([
                    'ID' => $ID,
                    'ToID' => $member->ID,
                    'IsRead' => false
                ])->first();
            if ($notification) {
                $notification->IsRead = true;
                $notification->write();
                return true;
            }
        }

        return false;
    }

    /**
     * Mark a Notification as seen, accepts a notification ID and returns a
     * boolean for success or failure.
     *
     * @param string|int $ID The ID of an InternalNotification for the current
     * logged in Member
     * @return bool true when marked seen otherwise false
     */
    public function see($ID): bool
    {
        $member = Security::getCurrentUser();
        if (!$member) {
            return false;
        }

        if ($ID) {
            $notification = InternalNotification::get()
                ->filter([
                    'ID' => $ID,
                    'ToID' => $member->ID
                ])->first();
            if ($notification) {
                if (!$notification->IsSeen) {
                    $notification->IsSeen = true;
                    $notification->write();
                }

                return true;
            }
        }

        return false;
    }
}

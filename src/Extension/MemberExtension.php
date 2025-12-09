<?php

namespace Symbiote\Notifications\Extension;

use SilverStripe\ORM\ArrayList;
use SilverStripe\Core\Extension;
use SilverStripe\View\ArrayData;
use Symbiote\Notifications\Model\InternalNotification;

/**
 * This extension is not applied by default
 * @extends \SilverStripe\Core\Extension<static>
 */
class MemberExtension extends Extension
{
    public function getNotifications(int $limit = 10, int $offset = 0, array $filter = []): ArrayList
    {

        /** @var \SilverStripe\Security\Member $owner */
        $owner = $this->getOwner();
        $filter = array_merge(
            $filter,
            ['ToID' => $owner->ID]
        );

        $notifications = ArrayList::create();

        foreach (InternalNotification::get()->filter($filter)->limit($limit, $offset) as $intNote) {
            $notification = ArrayData::create($intNote->toMap());
            /** @phpstan-ignore method.notFound */
            $notification->setField('FromUsername', $intNote->From()->getNotificationUsername());
            $notifications->push($notification);
        }

        return $notifications;
    }

    public function getNotificationUsername(): string
    {

        /** @var \SilverStripe\Security\Member $owner */
        $owner = $this->getOwner();
        if ($owner->Username) {
            return $owner->Username;
        }

        return $owner->getTitle();
    }
}

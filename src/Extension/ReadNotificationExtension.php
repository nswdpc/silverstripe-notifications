<?php

namespace Symbiote\Notifications\Extension;

use SilverStripe\Core\Extension;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use Symbiote\Notifications\Model\InternalNotification;

/**
 * This extension is not applied by default
 * @extends \SilverStripe\Core\Extension<static>
 */
class ReadNotificationExtension extends Extension
{
    public function onBeforeInit()
    {
        $member = Security::getCurrentUser();
        /** @var \SilverStripe\Control\Controller $owner */
        $owner = $this->getOwner();
        $notificationId = $owner->getRequest()->getVar('notification');
        if ($member && $notificationId) {
            $note = InternalNotification::get()->byID($notificationId);
            if ($note && $note->ToID == $member->ID) {
                $note->IsRead = 1;
                $note->write();
            }
        }
    }
}

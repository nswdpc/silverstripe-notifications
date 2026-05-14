<?php

namespace Symbiote\Notifications\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use Symbiote\MultiValueField\ORM\FieldType\MultiValueField;
use Symbiote\MultiValueField\Fields\KeyValueField;
use SilverStripe\Security\Permission;

/**
 * @property string $Title
 * @property ?string $Message
 * @property ?string $SentOn
 * @property bool $IsRead
 * @property bool $IsSeen
 * @property mixed $Context
 * @property int $ToID
 * @property int $FromID
 * @property int $SourceObjectID
 * @property int $SourceNotificationID
 * @method \SilverStripe\Security\Member To()
 * @method \SilverStripe\Security\Member From()
 * @method \SilverStripe\ORM\DataObject SourceObject()
 * @method \Symbiote\Notifications\Model\SystemNotification SourceNotification()
 */
class InternalNotification extends DataObject
{
    private static string $table_name = 'InternalNotification';

    private static array $db = [
        'Title' => 'Varchar(255)',
        'Message'   => 'Text',
        'SentOn' => 'Datetime',
        'IsRead'    => 'Boolean',
        'IsSeen'    => 'Boolean',
        'Context' => MultiValueField::class,
    ];

    private static array $has_one = [
        'To'        => Member::class,
        'From'      => Member::class,
        'SourceObject' => DataObject::class,
        'SourceNotification' => SystemNotification::class,
    ];

    private static array $summary_fields = [
        'Title' => 'Title',
        'To.Name' => 'To',
        'SentOn' => 'Sent on',
        'IsSeen.Nice' => 'Seen?',
        'IsRead.Nice' => 'Read?'
    ];

    private static string $default_sort = 'ID DESC';

    #[\Override]
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if ($this->IsRead) {
            $this->IsSeen = true;
        }
    }

    #[\Override]
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->replaceField('Context', KeyValueField::create('Context'));
        return $fields;
    }

    #[\Override]
    public function canView($member = null)
    {
        $member = $member ?: Security::getCurrentUser();
        if (!$member) {
            return false;
        }

        if (Permission::check('ADMIN')) {
            return true;
        }

        return (!$this->ID || $this->ToID == $member->ID || $this->FromID == $member->ID);
    }

    #[\Override]
    public function canEdit($member = null)
    {
        $member = $member ?: Security::getCurrentUser();
        if (!$member) {
            return false;
        }

        if (Permission::check('ADMIN')) {
            return true;
        }

        return (!$this->ID || $this->ToID == $member->ID);
    }
}

<?php

namespace Symbiote\Notifications\Model;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Core\Injector\Injector;
use Symbiote\Notifications\Service\NotificationService;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Security\Permission;
use Symbiote\MultiValueField\Fields\KeyValueField;
use Symbiote\Notifications\Controller\NotificationAdmin;

class BroadcastNotification extends DataObject implements NotifiedOn
{
    private static string $table_name = 'BroadcastNotification';

    private static array $db = [
        'Title' => 'Varchar(255)',
        'Content' => 'Text',
        'SendNow' => 'Boolean',
        'IsPublic'  => 'Boolean',
        'Context' => 'MultiValueField'
    ];

    private static array $many_many = [
        'Groups' => Group::class
    ];

    public function onBeforeWrite()
    {
        if ($this->SendNow) {
            $this->SendNow = false;
            Injector::inst()->get(NotificationService::class)->notify(
                'BROADCAST',
                $this
            );
        }

        parent::onBeforeWrite();
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->dataFieldByName('IsPublic')->setRightTitle('Indicate whether this can be displayed to public users');

        if ($this->ID) {
            $fields->dataFieldByName('SendNow')->setRightTitle('If selected, this notification will be broadcast to all users in groups selected below');

            $fields->removeByName('Groups');

            $fields->addFieldToTab('Root.Main', ListboxField::create('Groups', 'Groups', Group::get()));
        } else {
            $fields->removeByName('SendNow');
        }

        $context = KeyValueField::create('Context')->setRightTitle('Add a Link and Title field here to provide context for this message');

        $fields->replaceField('Context', $context);

        return $fields;
    }

    public function getAvailableKeywords(): array
    {
        $fields = $this->getNotificationTemplateData();
        $names = array_keys($fields);
        return array_combine($names, $names);
    }

    /**
     * Gets an associative array of data that can be accessed in
     * notification fields and templates
     */
    public function getNotificationTemplateData(): array
    {
        $fields = $this->Context->getValues();
        if (!is_array($fields)) {
            $fields = [];
        }

        $fields['Content'] = $this->Content;
        return $fields;
    }

    /**
     * Gets the list of recipients for a given notification event, based on this object's
     * state.
     * @param string $event The Identifier of the notification being sent
     * @return array of Member objects
     */
    public function getRecipients($event):array
    {
        $groupIds = $this->Groups()->column('ID');
        if (count($groupIds) !== 0) {
            return Member::get()->filter('Groups.ID', $groupIds)->toArray();
        }

        return [];
    }

    public function Link(): ?string
    {
        $context = $this->Context->getValues();
        return $context['Link'] ?? null;
    }

    public function canCreate($member = null, $context = [])
    {
        return Permission::check('CMS_ACCESS_' . NotificationAdmin::class) || parent::canCreate($member, $context);
    }

    public function canDelete($member = null)
    {
        return Permission::check('CMS_ACCESS_' . NotificationAdmin::class) || parent::canDelete($member);
    }

    public function canView($member = null)
    {
        return $this->IsPublic || (Permission::check('CMS_ACCESS_' . NotificationAdmin::class) || parent::canView($member));
    }

    public function canEdit($member = null)
    {
        return Permission::check('CMS_ACCESS_' . NotificationAdmin::class) || parent::canEdit($member);
    }
}

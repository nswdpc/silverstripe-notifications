<?php

namespace Symbiote\Notifications\Job;

use SilverStripe\ORM\DataObject;
use Symbiote\Notifications\Model\SystemNotification;
use Symbiote\Notifications\Service\NotificationService;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJob;

/* All code covered by the BSD license located at http://silverstripe.org/bsd-license/ */

if (class_exists(AbstractQueuedJob::class)) {

    /**
     * A queued job for sending notifications
     * @author Marcus Nyeholt <marcus@symbiote.com.au>
     */
    class SendNotificationJob extends AbstractQueuedJob
    {
        protected ?int $notificationID = null;

        protected ?int $contextID = null;

        protected ?string $contextClass = null;

        protected array $extraData = [];

        protected array $sendTo = [];

        /**
         * SendNotificationJob constructor.
         * @param \Symbiote\Notifications\Model\SystemNotification|null $notification
         * @param \SilverStripe\ORM\DataObject|null                     $context
         */
        public function __construct(
            ?SystemNotification $notification = null,
            ?DataObject $context = null,
            array $data = []
        ) {
            if ($notification instanceof \Symbiote\Notifications\Model\SystemNotification) {
                $this->notificationID = $notification->ID;
                $this->contextID = $context->ID;
                if ($context instanceof \SilverStripe\ORM\DataObject) {
                    $this->contextClass = $context::class;
                }

                $this->extraData = $data;
            }
        }

        public function getNotification(): ?SystemNotification
        {
            return SystemNotification::get()->byID($this->notificationID);
        }

        public function getContext(): ?DataObject
        {
            if ($this->contextID) {
                return DataObject::get_by_id($this->contextClass, $this->contextID);
            }

            return null;
        }

        public function getTitle(): string
        {
            $context = $this->getContext();
            $notification = $this->getNotification();

            if ($context instanceof \SilverStripe\ORM\DataObject) {
                $title = '';
                if ($context->hasField('Title')) {
                    $title = $context->Title;
                } elseif ($context->hasField('Name')) {
                    $title = $context->Name;
                } elseif ($context->hasField('Description')) {
                    $title = $context->Description;
                } else {
                    $title = '#'.$context->ID;
                }
            } else {
                $title = $notification->Title;
            }

            return 'Sending notification "'.$notification->Description.'" for '.$title;
        }

        public function getJobType(): string
        {
            $notification = $this->getNotification();
            $recipients = $notification->getRecipients($this->getContext());
            $sendTo = [];

            foreach ($recipients as $r) {
                $sendTo[$r->ID] = $r->ClassName;
            }

            $this->sendTo = $sendTo;
            /* @phpstan-ignore property.notFound */
            $this->totalSteps = count($this->sendTo);

            /* @phpstan-ignore class.notFound, class.notFound */
            return $this->totalSteps > 5 ? QueuedJob::QUEUED : QueuedJob::IMMEDIATE;
        }

        public function process()
        {
            $remaining = $this->sendTo;

            // if there's no more, we're done!
            if ($remaining === []) {
                /* @phpstan-ignore property.notFound */
                $this->isComplete = true;
                return;
            }

            /* @phpstan-ignore property.notFound, property.notFound */
            $this->currentStep++;

            $keys = array_keys($remaining);
            $toID = array_shift($keys);
            $toClass = $remaining[$toID];
            unset($remaining[$toID]);

            $notification = $this->getNotification();
            $context = $this->getContext();

            $service = singleton(NotificationService::class);

            $user = DataObject::get_by_id($toClass, (int)$toID);

            $data = [];

            // extra data is an array - need to deserialise it!!
            foreach ($this->extraData as $k => $v) {
                $data[$k] = $v;
            }

            // now send to the single user
            $service->sendToUser($notification, $context, $user, $data);

            // save new data
            $this->sendTo = $remaining;

            if (count($remaining) <= 0) {
                /* @phpstan-ignore property.notFound */
                $this->isComplete = true;
            }
        }
    }
}

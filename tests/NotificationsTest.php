<?php

namespace Symbiote\Notifications\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use Symbiote\Notifications\Model\SystemNotification;
use Symbiote\Notifications\Service\EmailNotificationSender;
use Symbiote\Notifications\Service\NotificationService;

/* All code covered by the BSD license located at http://silverstripe.org/bsd-license/ */

/**
 * @author Marcus Nyeholt <marcus@symbiote.com.au>
 */
class NotificationsTest extends SapphireTest
{
    protected static $fixture_file = __DIR__.'/NotificationsTest.yml';

    protected static $extra_dataobjects = [
        NotifyOnThis::class,
    ];

    public function testNotificationTrigger(): void
    {
        // create a new notification and add it to the object
        $notification = \Symbiote\Notifications\Model\SystemNotification::create();
        $notification->Title = "Notify on event";
        $notification->Description = 'Notifies on an event occurring';
        $notification->NotificationText = 'This is a notfication to $Member.Email about $NotifyOnThis.Title';
        $notification->Identifier = 'NOTIFY_ON_EVENT';
        $notification->NotifyOnClass = NotifyOnThis::class;
        $notification->write();

        // okay, add it to our page
        $page = $this->objFromFixture(NotifyOnThis::class, 'not1');

        $ns = new NotificationService();
        $ds = new DummyNotificationSender();

        Config::modify()->set(NotificationService::class, 'use_queues', false);

        $ns->addSender('dummy', $ds);
        $ns->setChannels(['dummy']);
        $ns->notify('NOTIFY_ON_EVENT', $page);

        // check that there was an actual notification added into our DS
        $this->assertEquals(1, count($ds->notifications));

        // check that the message was formatted appropriately
        $msg = $ds->notifications[0];
        $this->assertEquals(
            "This is a notfication to dummy@nowhere.com about Some Data Object",
            $msg->text
        );
    }

    public function testSpecificChannels(): void
    {
        $notification = \Symbiote\Notifications\Model\SystemNotification::create();
        $notification->Title = "Notify on event";
        $notification->Description = 'Notifies on an event occurring';
        $notification->NotificationText = 'This is a notfication to $Member.Email about $NotifyOnThis.Title';
        $notification->Identifier = 'NOTIFY_ON_EVENT';
        $notification->NotifyOnClass = NotifyOnThis::class;
        $notification->write();

        // okay, add it to our page
        $page = $this->objFromFixture(NotifyOnThis::class, 'not1');

        $ns = new NotificationService();
        $ds = new DummyNotificationSender();

        Config::modify()->set(NotificationService::class, 'use_queues', false);

        $ns->addSender('dummy', $ds);
        $ns->notify('NOTIFY_ON_EVENT', $page, [], 'dummy');

        // now check that there was an actual notification added into our DS
        $this->assertEquals(1, count($ds->notifications));

        $msg = $ds->notifications[0];

        $this->assertEquals(
            "This is a notfication to dummy@nowhere.com about Some Data Object",
            $msg->text
        );
    }

    public function testSendEmailNotification(): void
    {
        $notification = \Symbiote\Notifications\Model\SystemNotification::create();
        $notification->Title = "Notify on event";
        $notification->Description = 'Notifies on an event occurring';
        $notification->NotificationText = 'This is a notfication to $Member.Email about $NotifyOnThis.Title';
        $notification->Identifier = 'NOTIFY_ON_EVENT';
        $notification->NotifyOnClass = NotifyOnThis::class;
        $notification->write();

        // okay, add it to our page
        $page = $this->objFromFixture(NotifyOnThis::class, 'not1');

        $ns = new NotificationService();

        Config::modify()->set(NotificationService::class, 'use_queues', false);
        Config::modify()->set(
            EmailNotificationSender::class,
            'send_notifications_from',
            'test@test.com'
        );
        Config::modify()->set(SystemNotification::class, 'default_template', false);

        $ns->setSenders(['email' => EmailNotificationSender::class]);
        $ns->setChannels(['email']);
        $ns->notify('NOTIFY_ON_EVENT', $page);

        // now check that there was an email sent
        $users = $page->getRecipients($notification->Identifier);
        $expectedTo = $users[0]->Email;
        $expectedFrom = 'test@test.com';
        $expectedSubject = $notification->Title;

        $this->assertEmailSent($expectedTo, $expectedFrom, $expectedSubject);
    }
}

<?php

declare(strict_types=1);

namespace Symbiote\Notifications\Controller;

use SilverStripe\Admin\ModelAdmin;
use Symbiote\Notifications\Model\SystemNotification;
use Symbiote\Notifications\Model\InternalNotification;
use Symbiote\Notifications\Model\BroadcastNotification;

/**
 * @author  marcus@symbiote.com.au
 * @author  nikspijkerman@gmail.com
 * @license http://silverstripe.org/bsd-license/
 */
class NotificationAdmin extends ModelAdmin
{
    private static array $managed_models = [
        SystemNotification::class,
        BroadcastNotification::class,
        InternalNotification::class,
    ];

    private static string $url_segment = 'notifications';

    private static string $menu_title = 'Notifications';

    private static string $menu_icon = 'symbiote/silverstripe-notifications: images/notifications-icon.svg';
}

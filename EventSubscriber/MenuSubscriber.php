<?php

/*
 * This file is part of the CustomCSSBundle.
 * All rights reserved by Kevin Papst (www.kevinpapst.de).
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\LhgTrackerBundle\EventSubscriber;

use App\Event\ConfigureMainMenuEvent;
use App\Utils\MenuItemModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MenuSubscriber implements EventSubscriberInterface
{
    private $security;

    public function __construct(AuthorizationCheckerInterface $security)
    {
        $this->security = $security;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConfigureMainMenuEvent::class => ['onMenuConfigure', 100],
        ];
    }

    public function onMenuConfigure(ConfigureMainMenuEvent $event): void
    {
        $auth = $this->security;

        // if (!$auth->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
        //     return;
        // }

        // $menu = $event->getSystemMenu();

        // if ($auth->isGranted('active_now_lhg_tracker')) {
        //     $menu->addChild(
        //         new MenuItemModel('lhg-tracker', 'Active Now', 'lhg-tracker', [], 'as fa-clock') 
        //     );
        // }
    }
}

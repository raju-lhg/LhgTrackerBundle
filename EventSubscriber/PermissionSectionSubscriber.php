<?php

/*
 * This file is part of the LhgTrackerBundle.
 * All rights reserved by Kevin Papst (www.kevinpapst.de).
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\LhgTrackerBundle\EventSubscriber;

use App\Event\PermissionSectionsEvent;
use App\Model\PermissionSection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PermissionSectionSubscriber implements EventSubscriberInterface
{
    /**
     * @CloudRequired do not change!
     */
    public const SECTION_TITLE = 'LHG Tracker (plugin)';

    public static function getSubscribedEvents(): array
    {
        return [
            PermissionSectionsEvent::class => ['onEvent', 100],
        ];
    }

    public function onEvent(PermissionSectionsEvent $event): void
    {
        $event->addSection(new PermissionSection(self::SECTION_TITLE, '_lhg_tracker'));
    }
}

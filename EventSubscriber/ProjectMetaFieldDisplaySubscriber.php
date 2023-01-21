<?php
namespace KimaiPlugin\LhgTrackerBundle\EventSubscriber;

use App\Entity\ActivityMeta;
use App\Entity\CustomerMeta;
use App\Entity\EntityWithMetaFields;
use App\Entity\MetaTableTypeInterface;
use App\Entity\ProjectMeta;
use App\Entity\TimesheetMeta;
use App\Event\ActivityMetaDisplayEvent;
use App\Event\CustomerMetaDisplayEvent;
use App\Event\ProjectMetaDisplayEvent;
use App\Event\TimesheetMetaDisplayEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Length;

class ProjectMetaFieldDisplaySubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [ 
            ProjectMetaDisplayEvent::class => ['loadProjectField', 200]
        ];
    }

    public function loadProjectField(ProjectMetaDisplayEvent $event)
    {
        $event->addField($this->prepareField(new ProjectMeta()));
    }


    private function prepareField(MetaTableTypeInterface $definition)
    {
        $definition
            ->setLabel('Allow Over Budget Tracking')
            ->setName('allow_over_budget_tracking')
            ->setType(BooleanType::class);

        return $definition;
    }
}
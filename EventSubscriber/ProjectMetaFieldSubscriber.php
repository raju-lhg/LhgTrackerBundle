<?php
namespace KimaiPlugin\LhgTrackerBundle\EventSubscriber;

use App\Entity\EntityWithMetaFields;
use App\Entity\MetaTableTypeInterface;
use App\Entity\ProjectMeta;
use App\Event\ProjectMetaDefinitionEvent;
use Doctrine\DBAL\Types\BooleanType; 
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\Constraints\Length;

class ProjectMetaFieldSubscriber implements EventSubscriberInterface{

    public static function getSubscribedEvents(): array
    {
        return [
            ProjectMetaDefinitionEvent::class => ['loadProjectMeta', 200]
        ];
    }

    public function loadProjectMeta(ProjectMetaDefinitionEvent $event)
    {
        $this->prepareEntity($event->getEntity(), new ProjectMeta());
    }

    private function prepareEntity(EntityWithMetaFields $entity, MetaTableTypeInterface $definition)
    {
        $definition
            ->setLabel('Allow Over Budget Tracking')
            ->setOptions(['help' => 'Select true if you want users to track over budget for this project.'])
            ->setName('allow_over_budget_tracking')
            ->setType(BooleanType::class)
            ->addConstraint(new Length(['max' => 11]))
            ->setIsVisible(true);

        $entity->setMetaField($definition);
    }
}
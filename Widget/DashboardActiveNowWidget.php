<?php

/*
 * This file is part of the LhgTrackerBundle for Kimai 2.
 * All rights reserved by Kevin Papst (www.kevinpapst.de).
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\LhgTrackerBundle\Widget;

use App\Entity\User;
use App\Repository\Query\UserQuery;
use App\Repository\UserRepository;
use App\Widget\Type\SimpleWidget;
use App\Widget\Type\UserWidget;
use App\Repository\TimesheetRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Project;
use App\Repository\Loader\TimesheetLoader;
use Doctrine\ORM\QueryBuilder as ORMQueryBuilder;
use App\Entity\Timesheet;

class DashboardActiveNowWidget extends SimpleWidget implements UserWidget
{
    /**
     * @var UserRepository
     */
    private $repository;
    private $time_sheet_repository;
    private $entityManager;

    public function __construct(
        TimesheetRepository $time_sheet_repository,
        UserRepository $repository,
        EntityManagerInterface $entityManager, 
        )
    {
        $this->repository = $repository;
        $this->entityManager = $entityManager;
        $this->time_sheet_repository = $time_sheet_repository;

        $this->setId('DashboardActiveNowWidget');
        $this->setTitle('Active Now');
        $this->setOptions([
            'user' => null,
            'id' => '',
        ]);
    }

    public function setUser(User $user): void
    {
        $this->setOption('user', $user);
    }

    public function getOptions(array $options = []): array
    {
        $options = parent::getOptions($options);

        if (empty($options['id'])) {
            $options['id'] = 'DashboardActiveNowWidget';
        }

        return $options;
    }

    public function getData(array $options = [])
    {
        $options = $this->getOptions($options);

        $activeEntries = $this->getActiveEntriesByProject();

        $userIds = [];
        foreach($activeEntries as $timesheet){
            array_push($userIds, $timesheet->getUser()->getId());
        }

        // $users = $this->repository->findByIds($userIds);

        return [
            'amount' => sizeof($userIds),
            // 'users' => $users,
            'timesheets' => $activeEntries
        ]; 
    }

    public function getTemplateName(): string
    {
        // return '@LhgTrackerBundle/Resources/views/widget.html.twig';
        return '@LhgTracker/widget.html.twig';
    }

    private function getActiveEntriesByProject(Project $project = null)
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('t')
            ->from(Timesheet::class, 't')
            ->andWhere($qb->expr()->isNotNull('t.begin'))
            ->andWhere($qb->expr()->isNull('t.end'))
            ->orderBy('t.begin', 'DESC');

        if (null !== $project) {
            $qb->andWhere('t.project = :project');
            $qb->setParameter('project', $project);
        }

        return $this->getHydratedTimeSheetResultsByQuery($qb, false);
    }

    protected function getHydratedTimeSheetResultsByQuery(ORMQueryBuilder $qb, bool $fullyHydrated = false): iterable
    {
        $results = $qb->getQuery()->getResult();

        $loader = new TimesheetLoader($qb->getEntityManager(), $fullyHydrated);
        $loader->loadResults($results);

        return $results;
    }
}

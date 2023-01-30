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

class DashboardActiveNowWidget extends SimpleWidget implements UserWidget
{
    /**
     * @var UserRepository
     */
    private $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;

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

        /** @var User $user */
        $user = $options['user'];

        $query = new UserQuery();
        $amount = $this->repository->countUsersForQuery($query);
        $query->setPageSize(8);

        return [
            'amount' => $amount,
            'users' => $this->repository->getPagerfantaForQuery($query)
        ];
    }

    public function getTemplateName(): string
    {
        return '@Demo/widget.html.twig';
    }
}

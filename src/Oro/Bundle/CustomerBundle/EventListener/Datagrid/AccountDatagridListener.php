<?php

namespace Oro\Bundle\CustomerBundle\EventListener\Datagrid;

use Oro\Bundle\CustomerBundle\Entity\Repository\AccountRepository;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\AccountUserProvider;

class AccountDatagridListener
{
    const ROOT_OPTIONS = '[options][accountUserOwner]';
    const ACCOUNT_USER_COLUMN = '[options][accountUserOwner][accountUserColumn]';

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var string
     */
    protected $entityAlias;

    /**
     * @var AccountUserProvider
     */
    protected $securityProvider;

    /**
     * @var AccountRepository
     */
    protected $repository;

    /**
     * @var array
     */
    protected $actionCallback;

    /**
     * @param AccountUserProvider $securityProvider
     * @param AccountRepository $repository
     * @param array $actionCallback
     */
    public function __construct(
        AccountUserProvider $securityProvider,
        AccountRepository $repository,
        array $actionCallback = null
    ) {
        $this->securityProvider = $securityProvider;
        $this->repository = $repository;
        $this->actionCallback = $actionCallback;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBeforeFrontendItems(BuildBefore $event)
    {
        if (!$this->getUser() instanceof CustomerUser) {
            return;
        }

        $config = $event->getConfig();

        if (null === $config->offsetGetByPath(self::ROOT_OPTIONS)) {
            return;
        }

        $entityClass = $config->getOrmQuery()->getRootEntity();
        if (!$entityClass) {
            return;
        }
        $entityAlias = $config->getOrmQuery()->getRootAlias();
        if (!$entityAlias) {
            return;
        }

        if (!$config->offsetGetByPath(ActionExtension::ACTION_CONFIGURATION_KEY)) {
            $config->offsetSetByPath(ActionExtension::ACTION_CONFIGURATION_KEY, $this->actionCallback);
        }

        $this->entityClass = $entityClass;
        $this->entityAlias = $entityAlias;

        $this->updateConfiguration($config);
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function updateConfiguration(DatagridConfiguration $config)
    {
        if ($this->permissionShowAllAccountItems()) {
            $this->showAllAccountItems($config);
        } elseif ($this->permissionShowAllAccountItemsForChild()) {
            $this->showAllAccountItems($config, true);
        }

        if (null !== ($accountUserColumn = $config->offsetGetByPath(self::ACCOUNT_USER_COLUMN))) {
            if (!$this->permissionShowAccountUserColumn()) {
                $this->removeAccountUserColumn($config, $accountUserColumn);
            }
        }
    }

    /**
     * @param DatagridConfiguration $config
     * @param bool $withChildCustomers
     */
    protected function showAllAccountItems(DatagridConfiguration $config, $withChildCustomers = false)
    {
        $config->offsetSetByPath(DatagridConfiguration::DATASOURCE_SKIP_ACL_APPLY_PATH, true);

        $user = $this->getUser();
        $customerId = $user->getCustomer()->getId();
        $ids = [$customerId];

        if ($withChildCustomers) {
            $ids = array_merge($ids, $this->repository->getChildrenIds($customerId));
        }

        $config->getOrmQuery()->addAndWhere(
            sprintf(
                '(%s.account IN (%s) OR %s.accountUser = %d)',
                $this->entityAlias,
                implode(',', $ids),
                $this->entityAlias,
                $user->getId()
            )
        );
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $column
     */
    protected function removeAccountUserColumn(DatagridConfiguration $config, $column)
    {
        $config
            ->offsetUnsetByPath(sprintf('[columns][%s]', $column))
            ->offsetUnsetByPath(sprintf('[sorters][columns][%s]', $column))
            ->offsetUnsetByPath(sprintf('[filters][columns][%s]', $column));
    }

    /**
     * @return CustomerUser
     */
    protected function getUser()
    {
        return $this->securityProvider->getLoggedUser();
    }

    /**
     * @return boolean
     */
    protected function permissionShowAllAccountItems()
    {
        return $this->securityProvider->isGrantedViewLocal($this->entityClass);
    }

    /**
     * @return boolean
     */
    protected function permissionShowAllAccountItemsForChild()
    {
        return $this->securityProvider->isGrantedViewDeep($this->entityClass) ||
            $this->securityProvider->isGrantedViewSystem($this->entityClass);
    }

    /**
     * @return boolean
     */
    protected function permissionShowAccountUserColumn()
    {
        return $this->securityProvider->isGrantedViewAccountUser($this->entityClass);
    }
}

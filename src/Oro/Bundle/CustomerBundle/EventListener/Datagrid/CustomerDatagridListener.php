<?php

namespace Oro\Bundle\CustomerBundle\EventListener\Datagrid;

use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerRepository;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\CustomerUserProvider;

class CustomerDatagridListener
{
    const ROOT_OPTIONS = '[options][customerUserOwner]';
    const ACCOUNT_USER_COLUMN = '[options][customerUserOwner][customerUserColumn]';

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var string
     */
    protected $entityAlias;

    /**
     * @var CustomerUserProvider
     */
    protected $securityProvider;

    /**
     * @var CustomerRepository
     */
    protected $repository;

    /**
     * @var array
     */
    protected $actionCallback;

    /**
     * @param CustomerUserProvider $securityProvider
     * @param CustomerRepository $repository
     * @param array $actionCallback
     */
    public function __construct(
        CustomerUserProvider $securityProvider,
        CustomerRepository $repository,
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
        if ($this->permissionShowAllCustomerItems()) {
            $this->showAllCustomerItems($config);
        } elseif ($this->permissionShowAllCustomerItemsForChild()) {
            $this->showAllCustomerItems($config, true);
        }

        if (null !== ($customerUserColumn = $config->offsetGetByPath(self::ACCOUNT_USER_COLUMN))) {
            if (!$this->permissionShowCustomerUserColumn()) {
                $this->removeCustomerUserColumn($config, $customerUserColumn);
            }
        }
    }

    /**
     * @param DatagridConfiguration $config
     * @param bool $withChildCustomers
     */
    protected function showAllCustomerItems(DatagridConfiguration $config, $withChildCustomers = false)
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
                '(%s.customer IN (%s) OR %s.customerUser = %d)',
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
    protected function removeCustomerUserColumn(DatagridConfiguration $config, $column)
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
    protected function permissionShowAllCustomerItems()
    {
        return $this->securityProvider->isGrantedViewLocal($this->entityClass);
    }

    /**
     * @return boolean
     */
    protected function permissionShowAllCustomerItemsForChild()
    {
        return $this->securityProvider->isGrantedViewDeep($this->entityClass) ||
            $this->securityProvider->isGrantedViewSystem($this->entityClass);
    }

    /**
     * @return boolean
     */
    protected function permissionShowCustomerUserColumn()
    {
        return $this->securityProvider->isGrantedViewCustomerUser($this->entityClass);
    }
}

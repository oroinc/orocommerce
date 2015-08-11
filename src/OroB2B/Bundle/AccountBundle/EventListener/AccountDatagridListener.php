<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\SecurityFacade;

class AccountDatagridListener
{
    const ROOT_OPTIONS          = '[options][accountUserOwner]';
    const ACCOUNT_USER_COLUMN   = '[options][accountUserOwner][accountUserColumn]';

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var string
     */
    protected $entityAlias;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBeforeFrontendItems(BuildBefore $event)
    {
        $config = $event->getConfig();

        if (null === ($config->offsetGetByPath(self::ROOT_OPTIONS))) {
            return;
        }

        if ([] === ($from = $config->offsetGetByPath('[source][query][from]', []))) {
            return;
        }

        $this->entityClass = $from[0]['table'];
        $this->entityAlias = $from[0]['alias'];

        if ($this->permissionShowAllAccountItems()) {
            $this->showAllAccountItems($config);
        }

        if (null !== ($accountUserColumn = $config->offsetGetByPath(self::ACCOUNT_USER_COLUMN))) {
            if (!$this->permissionShowAccountUserColumn()) {
                $this->removeAccountUserColumn($config, $accountUserColumn);
            }
        }
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function showAllAccountItems(DatagridConfiguration $config)
    {
        $config->offsetSetByPath(Builder::DATASOURCE_SKIP_ACL_CHECK, true);

        /* @var $user AccountUser */
        $user = $this->securityFacade->getLoggedUser();

        $where = $config->offsetGetByPath('[source][query][where]', ['and' => []]);

        $where['and'][] = sprintf(
            '%s.account = %d OR %s.accountUser = %d',
            $this->entityAlias,
            $user->getAccount()->getId(),
            $this->entityAlias,
            $user->getId()
        );

        $config->offsetSetByPath('[source][query][where]', $where);
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function removeAccountUserColumn(DatagridConfiguration $config, $column)
    {
        $config
            ->offsetUnsetByPath(sprintf('[columns][%s]', $column))
            ->offsetUnsetByPath(sprintf('[sorters][columns][%s]', $column))
            ->offsetUnsetByPath(sprintf('[filters][columns][%s]', $column))
        ;
    }

    /**
     * @return boolean
     */
    protected function permissionShowAllAccountItems()
    {
        return $this->securityFacade->isGrantedViewLocal($this->entityClass);
    }

    /**
     * @return boolean
     */
    protected function permissionShowAccountUserColumn()
    {
        return $this->securityFacade->isGrantedViewAccountUser($this->entityClass);
    }
}

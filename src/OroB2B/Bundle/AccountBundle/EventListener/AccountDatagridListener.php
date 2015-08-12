<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Security\AccountUserProvider;

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
     * @param AccountUserProvider $securityProvider
     */
    public function __construct(AccountUserProvider $securityProvider)
    {
        $this->securityProvider = $securityProvider;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBeforeFrontendItems(BuildBefore $event)
    {
        if (!$this->getUser() instanceof AccountUser) {
            return;
        }

        $config = $event->getConfig();

        if (null === $config->offsetGetByPath(self::ROOT_OPTIONS)) {
            return;
        }

        if ([] === ($from = $config->offsetGetByPath('[source][query][from]', []))) {
            return;
        }

        $fromFirst = reset($from);

        $this->entityClass = $fromFirst['table'];
        $this->entityAlias = $fromFirst['alias'];

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

        $user = $this->getUser();

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
     * @return AccountUser
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
    protected function permissionShowAccountUserColumn()
    {
        return $this->securityProvider->isGrantedViewAccountUser($this->entityClass);
    }
}

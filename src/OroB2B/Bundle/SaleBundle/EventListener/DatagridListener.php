<?php

namespace OroB2B\Bundle\SaleBundle\EventListener;

use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class DatagridListener
{
    /**
     * @var string
     */
    protected $quoteClass;

    /**
     * @var string
     */
    protected $accountUserClass;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param string $quoteClass
     * @param string $accountUserClass
     * @param SecurityFacade $securityFacade
     */
    public function __construct($quoteClass, $accountUserClass, SecurityFacade $securityFacade)
    {
        $this->quoteClass = $quoteClass;
        $this->accountUserClass = $accountUserClass;
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBeforeFrontendQuotes(BuildBefore $event)
    {
        $config = $event->getConfig();

        if ($this->permissionShowAllAccountQuotes()) {
            $this->showAllAccountQuotes($config);
        }

        if (!$this->permissionShowUserColumn()) {
            $this->removeAccountUserNameColumn($config);
        }
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function showAllAccountQuotes(DatagridConfiguration $config)
    {
        $config->offsetSetByPath(Builder::DATASOURCE_SKIP_ACL_CHECK, true);

        /* @var $user AccountUser */
        $user = $this->securityFacade->getLoggedUser();

        $where = $config->offsetGetByPath('[source][query][where]', ['and' => []]);

        $where['and'][] = sprintf(
            'quote.account = %d OR quote.accountUser = %d',
            $user->getAccount()->getId(),
            $user->getId()
        );

        $config->offsetSetByPath('[source][query][where]', $where);
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function removeAccountUserNameColumn(DatagridConfiguration $config)
    {
        $config
            ->offsetUnsetByPath('[columns][accountUserName]')
            ->offsetUnsetByPath('[sorters][columns][accountUserName]')
            ->offsetUnsetByPath('[filters][columns][accountUserName]')
        ;
    }

    /**
     * @return boolean
     */
    protected function permissionShowAllAccountQuotes()
    {
        return $this->securityFacade->isGrantedClassMask(EntityMaskBuilder::MASK_VIEW_LOCAL, $this->quoteClass);
    }

    /**
     * @return boolean
     */
    protected function permissionShowUserColumn()
    {
        if (!$this->securityFacade->isGrantedClassPermission(
            BasicPermissionMap::PERMISSION_VIEW,
            $this->accountUserClass
        )) {
            return false;
        }

        if (!$this->securityFacade->isGrantedClassMask(EntityMaskBuilder::MASK_VIEW_LOCAL, $this->quoteClass)) {
            return false;
        }

        return true;
    }
}

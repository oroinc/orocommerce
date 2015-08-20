<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class DatagridListener
{
    const ACCOUNT_USER_ROLE_COLUMN = 'account_user_role_label';
    
    /**
     * @param BuildBefore $event
     */
    public function onBuildBeforeWebsites(BuildBefore $event)
    {
        $this->addAccountUserRoleRelation($event->getConfig(), 'website MEMBER OF accountUserRole.websites');
    }
    
    /**
     * @param DatagridConfiguration $config
     * @param string $joinCondition
     */
    protected function addAccountUserRoleRelation(DatagridConfiguration $config, $joinCondition)
    {
        // select
        $select = 'accountUserRole.label as ' . self::ACCOUNT_USER_ROLE_COLUMN;
        $this->addConfigElement($config, '[source][query][select]', $select);
        // left join
        $leftJoin = [
            'join' => 'OroB2BAccountBundle:AccountUserRole',
            'alias' => 'accountUserRole',
            'conditionType' => 'WITH',
            'condition' => $joinCondition
        ];
        $this->addConfigElement($config, '[source][query][join][left]', $leftJoin);
        // column
        $column = ['label' => 'orob2b.account.accountuserrole.entity_label'];
        $this->addConfigElement($config, '[columns]', $column, self::ACCOUNT_USER_ROLE_COLUMN);
        // sorter
        $sorter = ['data_name' => self::ACCOUNT_USER_ROLE_COLUMN];
        $this->addConfigElement($config, '[sorters][columns]', $sorter, self::ACCOUNT_USER_ROLE_COLUMN);
        // filter
        $filter = [
            'type' => 'entity',
            'data_name' => 'accountUserRole.id',
            'options' => [
                'field_type' => 'entity',
                'field_options' => [
                    'class' => 'OroB2BAccountBundle:AccountUserRole',
                    'property' => 'label',
                ]
            ]
        ];
        $this->addConfigElement($config, '[filters][columns]', $filter, self::ACCOUNT_USER_ROLE_COLUMN);
    }
    
    /**
     * @param DatagridConfiguration $config
     * @param string $path
     * @param mixed $element
     * @param mixed $key
     */
    protected function addConfigElement(DatagridConfiguration $config, $path, $element, $key = null)
    {
        $select = $config->offsetGetByPath($path);
        if ($key) {
            $select[$key] = $element;
        } else {
            $select[] = $element;
        }
        $config->offsetSetByPath($path, $select);
    }
}

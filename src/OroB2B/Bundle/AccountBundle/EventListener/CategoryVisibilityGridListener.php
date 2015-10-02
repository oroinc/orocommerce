<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\Query\Parameter;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;

class CategoryVisibilityGridListener
{
    const ACCOUNT_CATEGORY_VISIBILITY_GRID = 'account-category-visibility-grid';
    const ACCOUNT_GROUP_CATEGORY_VISIBILITY_GRID = 'account-group-category-visibility-grid';
    const ACCOUNT_CATEGORY_VISIBILITY_ALIAS = 'accountCategoryVisibility.visibility';
    const ACCOUNT_GROUP_CATEGORY_VISIBILITY_ALIAS = 'accountGroupCategoryVisibility.visibility';

    /**
     * @param OrmResultBefore $event
     */
    public function onResultBefore(OrmResultBefore $event)
    {
        if (!$this->canHandleGrid($event->getDatagrid()->getName())
            || !$this->isFilteredByDefaultValue($event->getDatagrid()->getParameters())
        ) {
            return;
        }

        /** @var OrmDatasource $dataSource */
        $dataSource = $event->getDatagrid()->getDatasource();
        /** @var array $parts */
        $parts = $dataSource->getQueryBuilder()->getDQLPart('where')->getParts();
        $regexp = implode('|', $this->getFieldAliases());
        foreach ($parts as $id => $part) {
            if (preg_match(sprintf('/%s/', $regexp), $part)) {
                unset($parts[$id]);
            }
        }
        $parts[] = $dataSource->getQueryBuilder()->expr()->isNull(
            $this->getFieldAlias($event->getDatagrid()->getName())
        );
        $dataSource->getQueryBuilder()->orWhere(
            call_user_func_array([
                $dataSource->getQueryBuilder()->expr(),
                'andX'
            ], $parts)
        );
        $event->getQuery()->setDQL($dataSource->getQueryBuilder()->getQuery()->getDQL());
    }

    /**
     * @param string $gridName
     *
     * @return bool
     */
    protected function canHandleGrid($gridName)
    {
        return in_array(
            $gridName,
            [
                self::ACCOUNT_CATEGORY_VISIBILITY_GRID,
                self::ACCOUNT_GROUP_CATEGORY_VISIBILITY_GRID
            ],
            true
        );
    }

    /**
     * @param ParameterBag $params
     *
     * @return bool
     */
    protected function isFilteredByDefaultValue(ParameterBag $params)
    {
        if (!$params->get('_filter')) {
            return false;
        }

        foreach ($params->get('_filter')['visibility']['value'] as $value) {
            if ($this->isDefaultValue($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    protected function isDefaultValue($value)
    {
        return in_array(
            $value,
            [
                AccountCategoryVisibility::PARENT_CATEGORY,
                AccountGroupCategoryVisibility::PARENT_CATEGORY
            ],
            true
        );
    }

    /**
     * @param string $gridName
     *
     * @return string
     */
    protected function getFieldAlias($gridName)
    {
        if ($gridName === self::ACCOUNT_CATEGORY_VISIBILITY_GRID) {
            $alias = self::ACCOUNT_CATEGORY_VISIBILITY_ALIAS;
        } else {
            $alias = self::ACCOUNT_GROUP_CATEGORY_VISIBILITY_ALIAS;
        }

        return $alias;
    }

    /**
     * @return array
     */
    protected function getFieldAliases()
    {
        return [
            self::ACCOUNT_CATEGORY_VISIBILITY_ALIAS,
            self::ACCOUNT_GROUP_CATEGORY_VISIBILITY_ALIAS
        ];
    }
}

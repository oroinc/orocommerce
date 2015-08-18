<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\Query\Parameter;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;

use OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroupCategoryVisibility;

class CategoryVisibilityGridListener
{
    /**
     * @param OrmResultBefore $event
     */
    public function onResultBefore(OrmResultBefore $event)
    {
        if ($this->canHandleGrid($event->getDatagrid()->getName())) {
            return;
        }

        if (!$this->isFilteredByParent($event->getDatagrid()->getParameters())) {
            return;
        }

        /** @var OrmDatasource $dataSource */
        $dataSource = $event->getDatagrid()->getDatasource();

        $parts = $dataSource->getQueryBuilder()->getDQLPart('where')->getParts();
        foreach ($parts as $id => $part) {
            if (preg_match('/visibility/', $part)) {
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
     * @param ParameterBag $params
     *
     * @return bool
     */
    protected function isFilteredByParent(ParameterBag $params)
    {
        if (!$params->get('f') && !$params->get('_filter')) {
            return false;
        }

        $configKey = !is_null($params->get('f')) ? 'f' : '_filter';
        foreach ($params->get($configKey)['visibility']['value'] as $value) {
            if (in_array($value, [
                AccountCategoryVisibility::PARENT_CATEGORY,
                AccountGroupCategoryVisibility::PARENT_CATEGORY
            ])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $gridName
     *
     * @return bool
     */
    protected function canHandleGrid($gridName)
    {
        return !in_array($gridName, [
            'account-category-visibility-grid',
            'accountgroup-category-visibility-grid'
        ]);
    }

    /**
     * @param string $gridName
     *
     * @return string
     */
    protected function getFieldAlias($gridName)
    {
        switch ($gridName) {
            case 'account-category-visibility-grid':
                $alias = 'accountCategoryVisibility.id';
                break;
            default:
                $alias = 'accountGroupCategoryVisibility.id';
        }

        return $alias;
    }
}

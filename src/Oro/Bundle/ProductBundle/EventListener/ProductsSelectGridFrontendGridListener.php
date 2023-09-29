<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;

/**
 * Grid listener to add where condition to restrict access only to allowed Product types
 */
class ProductsSelectGridFrontendGridListener
{
    private array $notAllowedProductTypes = [];

    public function setNotAllowedProductTypes(array $notAllowedProductTypes): void
    {
        $this->notAllowedProductTypes = $notAllowedProductTypes;
    }

    public function onBuildAfter(BuildAfter $event): void
    {
        $datasource = $event->getDatagrid()->getDatasource();
        if (!$datasource instanceof SearchDatasource) {
            return;
        }

        if (empty($this->notAllowedProductTypes)) {
            return;
        }

        $criteria = $datasource->getSearchQuery()->getCriteria();
        $criteria->andWhere(
            Criteria::expr()->notIn(
                'text.type',
                $this->notAllowedProductTypes
            )
        );
    }
}

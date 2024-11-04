<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Element;

use Oro\Bundle\FrontendBundle\Tests\Behat\Element\Grid;

class FrontendProductGrid extends Grid
{
    const DEFAULT_MAPPINGS = [
        'GridRow' => 'ProductFrontendGridRow',
        'GridRowStrict' => 'ProductFrontendGridRow',
        'GridTable' => 'ProductFrontendGridTable',
        'GridToolbarPaginator' => 'ProductFrontendGridToolbarPaginator',
        'MassActionHeadCheckbox' => 'ProductFrontendMassActionHeadCheckbox',
        'MassActionButton' => 'ProductFrontendMassActionButton',
        'GridMassActionMenu' => 'ProductFrontendGridMassActionMenu',
        'GridColumnManager' => 'FrontendGridColumnManager',
        'GridFilterManager' => 'FrontendGridFilterManager',
    ];

    #[\Override]
    public function getRows()
    {
        return $this->getElements($this->getMappedChildElementName(static::TABLE_ROW_STRICT_ELEMENT));
    }
}

<?php

namespace Oro\Bundle\VisibilityBundle\Api\Filter;

use Oro\Bundle\VisibilityBundle\Api\VisibilityIdHelper;

/**
 * The factory to create VisibilityIdFilter.
 */
class VisibilityIdFilterFactory
{
    private VisibilityIdHelper $visibilityIdHelper;

    public function __construct(VisibilityIdHelper $visibilityIdHelper)
    {
        $this->visibilityIdHelper = $visibilityIdHelper;
    }

    /**
     * Creates a new instance of VisibilityIdFilter.
     */
    public function createFilter(string $dataType): VisibilityIdFilter
    {
        $filter = new VisibilityIdFilter($dataType);
        $filter->setVisibilityIdHelper($this->visibilityIdHelper);

        return $filter;
    }
}

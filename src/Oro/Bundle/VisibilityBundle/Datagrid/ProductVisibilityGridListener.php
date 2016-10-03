<?php

namespace Oro\Bundle\VisibilityBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Event\BuildAfter;

class ProductVisibilityGridListener
{
    /**
     * Restrict visibility result set by scope
     *
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {

    }
}

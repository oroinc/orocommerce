<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Element;

use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridPaginatorInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class ProductFrontendGridToolbarPaginator extends Element implements GridPaginatorInterface
{
    #[\Override]
    public function getTotalRecordsCount()
    {
        preg_match('/(?P<count>\d+)\s+(product)/i', $this->getText(), $matches);

        return isset($matches['count']) ? (int) $matches['count'] : 0;
    }

    #[\Override]
    public function getTotalPageCount()
    {
        return 1;
    }
}

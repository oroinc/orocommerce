<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Datagrid\Filter;

use Oro\Bundle\CatalogBundle\Datagrid\Filter\SubcategoryFilter;

class SubcategoryFilterTest extends \PHPUnit_Framework_TestCase
{
    /** @var SubcategoryFilter */
    protected $filter;

    protected function setUp()
    {
        $this->filter = new SubcategoryFilter();
    }
}

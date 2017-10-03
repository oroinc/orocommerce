<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Oro\Bundle\CatalogBundle\Provider\SubcategoryProvider;

class SubcategoryProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var SubcategoryProvider */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new SubcategoryProvider();
    }
}

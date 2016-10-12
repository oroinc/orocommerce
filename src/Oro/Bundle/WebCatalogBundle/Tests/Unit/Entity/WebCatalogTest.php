<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Entity;

use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class WebCatalogTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new WebCatalog(), [
            ['name', 'Catalog'],
            ['description', 'Catalog Description']
        ]);
    }
}

<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Entity;

use Oro\Bundle\SEOBundle\Entity\WebCatalogProductLimitation;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class WebCatalogProductLimitationTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new WebCatalogProductLimitation(), [
            ['id', 1],
            ['productId', 42]
        ]);
    }
}

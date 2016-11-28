<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Entity;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CheckoutSourceTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['deleted', true]
        ];

        $entity = new CheckoutSource();
        $this->assertPropertyAccessors($entity, $properties);
    }
}

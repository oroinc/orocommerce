<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Entity;

use Oro\Bundle\UPSBundle\Entity\UPSShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class UPSShippingServiceTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(new UPSShippingService(), [
            ['code', 'some code'],
            ['description', 'some description'],
            ['transport', new UPSTransport()]
        ]);
    }
}

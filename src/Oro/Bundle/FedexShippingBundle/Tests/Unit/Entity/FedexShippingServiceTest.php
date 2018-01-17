<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Entity;

use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingServiceRule;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class FedexShippingServiceTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(new FedexShippingService(), [
            ['id', 12],
            ['code', 'code'],
            ['description', 'description'],
            ['rule', new ShippingServiceRule()],
        ]);
    }
}

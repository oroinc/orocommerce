<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\ShippingMethod\Identifier;

use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\Identifier\FedexMethodTypeIdentifierGenerator;
use PHPUnit\Framework\TestCase;

class FedexMethodTypeIdentifierGeneratorTest extends TestCase
{
    const CODE = 'code';

    public function testGenerate()
    {
        $service = new FedexShippingService();
        $service->setCode(self::CODE);

        static::assertSame(
            self::CODE,
            (new FedexMethodTypeIdentifierGenerator())->generate($service)
        );
    }
}

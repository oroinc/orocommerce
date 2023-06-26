<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\ShippingMethod\Identifier;

use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\Identifier\FedexMethodTypeIdentifierGenerator;
use PHPUnit\Framework\TestCase;

class FedexMethodTypeIdentifierGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $code = 'code';

        $service = new FedexShippingService();
        $service->setCode($code);

        self::assertSame(
            $code,
            (new FedexMethodTypeIdentifierGenerator())->generate($service)
        );
    }
}

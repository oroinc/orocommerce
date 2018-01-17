<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Transformer;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Transformer\FedexToShippingWeightUnitTransformer;
use PHPUnit\Framework\TestCase;

class FedexToShippingWeightUnitTransformerTest extends TestCase
{
    public function testTransform()
    {
        static::assertSame(
            'kg',
            (new FedexToShippingWeightUnitTransformer())->transform(FedexIntegrationSettings::UNIT_OF_WEIGHT_KG)
        );
        static::assertSame(
            'lbs',
            (new FedexToShippingWeightUnitTransformer())->transform(FedexIntegrationSettings::UNIT_OF_WEIGHT_LB)
        );
    }

    public function testReverseTransform()
    {
        static::assertSame(
            FedexIntegrationSettings::UNIT_OF_WEIGHT_KG,
            (new FedexToShippingWeightUnitTransformer())->reverseTransform('kg')
        );
        static::assertSame(
            FedexIntegrationSettings::UNIT_OF_WEIGHT_LB,
            (new FedexToShippingWeightUnitTransformer())->reverseTransform('lbs')
        );
    }
}

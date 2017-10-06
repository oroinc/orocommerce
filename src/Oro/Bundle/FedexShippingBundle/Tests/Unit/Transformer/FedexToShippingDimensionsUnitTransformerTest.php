<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Transformer;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Transformer\FedexToShippingDimensionsUnitTransformer;
use PHPUnit\Framework\TestCase;

class FedexToShippingDimensionsUnitTransformerTest extends TestCase
{
    public function testTransform()
    {
        static::assertSame(
            'cm',
            (new FedexToShippingDimensionsUnitTransformer())->transform(FedexIntegrationSettings::DIMENSION_CM)
        );
        static::assertSame(
            'inch',
            (new FedexToShippingDimensionsUnitTransformer())->transform(FedexIntegrationSettings::DIMENSION_IN)
        );
    }

    public function testReverseTransform()
    {
        static::assertSame(
            FedexIntegrationSettings::DIMENSION_CM,
            (new FedexToShippingDimensionsUnitTransformer())->reverseTransform('cm')
        );
        static::assertSame(
            FedexIntegrationSettings::DIMENSION_IN,
            (new FedexToShippingDimensionsUnitTransformer())->reverseTransform('inch')
        );
    }
}

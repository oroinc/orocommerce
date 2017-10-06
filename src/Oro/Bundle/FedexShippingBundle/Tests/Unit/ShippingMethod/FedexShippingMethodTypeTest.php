<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\ShippingMethod;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Form\Type\FedexShippingMethodOptionsType;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\FedexShippingMethodType;
use PHPUnit\Framework\TestCase;

class FedexShippingMethodTypeTest extends TestCase
{
    const IDENTIFIER = 'id';
    const LABEL = 'label';

    public function testGetters()
    {
        $type = new FedexShippingMethodType(
            self::IDENTIFIER,
            self::LABEL,
            new FedexIntegrationSettings()
        );

        static::assertSame(self::IDENTIFIER, $type->getIdentifier());
        static::assertSame(self::LABEL, $type->getLabel());
        static::assertSame(0, $type->getSortOrder());
        static::assertSame(FedexShippingMethodOptionsType::class, $type->getOptionsConfigurationFormType());
    }
}

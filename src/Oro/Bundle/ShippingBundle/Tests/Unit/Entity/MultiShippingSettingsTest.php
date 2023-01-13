<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Entity\MultiShippingSettings;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

class MultiShippingSettingsTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testEntity()
    {
        $entity = new MultiShippingSettings();
        $properties = [
            ['id', 1],
            ['channel', new Channel()],
        ];

        $this->assertPropertyAccessors($entity, $properties);
        $this->assertInstanceOf(ParameterBag::class, $entity->getSettingsBag());
    }
}

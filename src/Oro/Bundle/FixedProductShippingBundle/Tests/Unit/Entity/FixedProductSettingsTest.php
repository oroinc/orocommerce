<?php

namespace Oro\Bundle\FixedProductShippingBundle\Tests\Unit\Entity;

use Oro\Bundle\FixedProductShippingBundle\Entity\FixedProductSettings;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

class FixedProductSettingsTest extends TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testAccessors(): void
    {
        $this->assertPropertyCollections(new FixedProductSettings(), [
            ['labels', new LocalizedFallbackValue()],
        ]);
    }

    public function testGetSettingsBagReturnsParameterBag(): void
    {
        $entity = new FixedProductSettings();
        $this->assertInstanceOf(ParameterBag::class, $entity->getSettingsBag());
    }
}

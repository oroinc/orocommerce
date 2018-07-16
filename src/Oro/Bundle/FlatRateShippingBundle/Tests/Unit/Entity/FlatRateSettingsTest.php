<?php

namespace Oro\Bundle\FlatRateShippingBundle\Tests\Unit\Entity;

use Oro\Bundle\FlatRateShippingBundle\Entity\FlatRateSettings;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\ParameterBag;

class FlatRateSettingsTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testAccessors()
    {
        static::assertPropertyCollections(new FlatRateSettings(), [
            ['labels', new LocalizedFallbackValue()],
        ]);
    }

    public function testGetSettingsBagReturnsParameterBag()
    {
        $entity = new FlatRateSettings();

        static::assertInstanceOf(ParameterBag::class, $entity->getSettingsBag());
    }
}

<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Entity;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\ParameterBag;

class PaymentTermSettingsTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testAccessors()
    {
        static::assertPropertyCollections(new PaymentTermSettings(), [
            ['labels', new LocalizedFallbackValue()],
        ]);
    }

    public function testGetSettingsBagReturnsParameterBag()
    {
        $entity = new PaymentTermSettings();

        static::assertInstanceOf(ParameterBag::class, $entity->getSettingsBag());
    }
}

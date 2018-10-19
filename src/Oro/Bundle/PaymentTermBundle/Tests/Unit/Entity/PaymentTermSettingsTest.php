<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Entity;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\ParameterBag;

class PaymentTermSettingsTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testAccessors()
    {
        static::assertPropertyCollections(
            new PaymentTermSettings(),
            [
                ['labels', new LocalizedFallbackValue()],
                ['shortLabels', new LocalizedFallbackValue()],
            ]
        );

        static::assertPropertyGetterReturnsSetValue(
            new PaymentTermSettings(),
            'channel',
            new Channel()
        );
    }

    public function testGetSettingsBagReturnsParameterBag()
    {
        $entity = new PaymentTermSettings();

        static::assertInstanceOf(ParameterBag::class, $entity->getSettingsBag());
    }
}

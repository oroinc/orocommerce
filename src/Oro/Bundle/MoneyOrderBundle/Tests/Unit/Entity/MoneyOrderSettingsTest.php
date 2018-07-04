<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Entity;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class MoneyOrderSettingsTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(new MoneyOrderSettings(), [
            ['payTo', 'payTo'],
            ['sendTo', 'sendTo'],
        ]);

        static::assertPropertyCollections(new MoneyOrderSettings(), [
            ['labels', new LocalizedFallbackValue()],
            ['shortLabels', new LocalizedFallbackValue()],
        ]);
    }

    public function testGetSettingsBagReturnsCorrectObject()
    {
        $payTo = 'Pay To';
        $sendTo = 'Send To';
        $label = (new LocalizedFallbackValue())->setString('Money Order');

        $entity = new MoneyOrderSettings();
        $entity->setPayTo($payTo)
            ->setSendTo($sendTo)
            ->addLabel($label);

        $settings = $entity->getSettingsBag();

        static::assertSame($payTo, $settings->get('pay_to'));
        static::assertSame($sendTo, $settings->get('send_to'));
        static::assertEquals([$label], $settings->get('labels'));
    }
}

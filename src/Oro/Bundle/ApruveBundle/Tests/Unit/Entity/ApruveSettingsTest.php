<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Entity;

use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class ApruveSettingsTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(new ApruveSettings(), [
            ['merchantId', '7b97ea0172e18cbd4d3bf21e2b525b2d'],
            ['apiKey', '213a9079914f3b5163c6190f31444528'],
            ['testMode', false],
            ['learnMoreUrl', 'https://test.apruve.com/apply/oro-test-store'],
            ['webhookToken', '8c02aef5-68df-4458-bad3-e2da636cee90'],
        ]);
    }

    public function testGetSettingsBag()
    {
        /** @var ApruveSettings $entity */
        $entity = $this->getEntity(
            ApruveSettings::class,
            [
                'merchantId' => '7b97ea0172e18cbd4d3bf21e2b525b2d',
                'apiKey' => '213a9079914f3b5163c6190f31444528',
                'testMode' => false,
                'learnMoreUrl' => 'https://test.apruve.com/apply/oro-test-store',
                'webhookToken' => '8c02aef5-68df-4458-bad3-e2da636cee90',
            ]
        );

        /** @var ParameterBag $result */
        $result = $entity->getSettingsBag();

        static::assertEquals('7b97ea0172e18cbd4d3bf21e2b525b2d', $result->get(ApruveSettings::MERCHANT_ID_KEY));
        static::assertEquals('213a9079914f3b5163c6190f31444528', $result->get(ApruveSettings::API_KEY_KEY));
        static::assertEquals(false, $result->get(ApruveSettings::TEST_MODE_KEY));
        static::assertEquals(
            'https://test.apruve.com/apply/oro-test-store',
            $result->get(ApruveSettings::LEARN_MORE_URL_KEY)
        );
        static::assertEquals('8c02aef5-68df-4458-bad3-e2da636cee90', $result->get(ApruveSettings::WEBHOOK_TOKEN_KEY));
    }
}

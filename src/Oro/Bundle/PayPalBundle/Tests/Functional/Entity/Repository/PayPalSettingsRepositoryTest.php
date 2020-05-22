<?php

namespace Oro\Bundle\PayPalBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PayPalBundle\Entity\Repository\PayPalSettingsRepository;
use Oro\Bundle\PayPalBundle\Tests\Functional\DataFixtures\LoadPayPalChannelData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PayPalSettingsRepositoryTest extends WebTestCase
{
    /**
     * @var PayPalSettingsRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        $this->initClient([], static::generateBasicAuthHeader());

        $this->loadFixtures([
            LoadPayPalChannelData::class,
        ]);

        $this->repository = static::getContainer()->get('doctrine')
            ->getManagerForClass('OroPayPalBundle:PayPalSettings')->getRepository('OroPayPalBundle:PayPalSettings');
    }

    /**
     * @dataProvider getEnabledSettingsByTypeDataProvider
     *
     * @param string $type
     * @param integer $expectedCount
     */
    public function testGetEnabledSettingsByType($type, $expectedCount)
    {
        $enabledSettings = $this->repository->getEnabledSettingsByType($type);
        $this->assertCount($expectedCount, $enabledSettings);
    }

    /**
     * @return array
     */
    public function getEnabledSettingsByTypeDataProvider()
    {
        return [
            [
                'type' => 'paypal_payflow_gateway',
                'expectedCount' => 1
            ],
            [
                'type' => 'paypal_payments_pro',
                'expectedCount' => 2
            ],
        ];
    }
}

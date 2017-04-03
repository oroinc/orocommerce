<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Method\Config\Factory;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;
use Oro\Bundle\ApruveBundle\Method\Config\Factory\ApruveConfigFactory;
use Oro\Bundle\ApruveBundle\Method\Config\Factory\ApruveConfigFactoryInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfig;

class ApruveConfigFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApruveConfigFactoryInterface
     */
    private $factory;

    /**
     * @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $localizationHelper;

    /**
     * @var IntegrationIdentifierGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $integrationIdentifierGenerator;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->integrationIdentifierGenerator = $this->createMock(IntegrationIdentifierGeneratorInterface::class);

        $this->factory = new ApruveConfigFactory(
            $this->localizationHelper,
            $this->integrationIdentifierGenerator
        );
    }

    public function testCreate()
    {
        $channelName = 'apruve';
        $label = 'Apruve';
        $shortLabel = 'Apruve (short)';
        $paymentMethodId = 'apruve_1';
        $apiKey = '213a9079914f3b5163c6190f31444528';
        $merchantId = '7b97ea0172e18cbd4d3bf21e2b525b2d';
        $testMode = false;

        $labelsCollection = $this->createLabelsCollectionMock();
        $shortLabelsCollection = $this->createLabelsCollectionMock();

        $channel = $this->createChannelMock();
        $channel
            ->expects(static::once())
            ->method('getName')
            ->willReturn($channelName);

        $this->integrationIdentifierGenerator
            ->expects(static::once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn($paymentMethodId);

        $this->localizationHelper
            ->expects(static::at(0))
            ->method('getLocalizedValue')
            ->with($labelsCollection)
            ->willReturn($label);

        $this->localizationHelper
            ->expects(static::at(1))
            ->method('getLocalizedValue')
            ->with($shortLabelsCollection)
            ->willReturn($shortLabel);

        $apruveSettings = $this->createApruveSettingsMock();
        $apruveSettings
            ->expects(static::once())
            ->method('getChannel')
            ->willReturn($channel);

        $apruveSettings
            ->expects(static::once())
            ->method('getLabels')
            ->willReturn($labelsCollection);

        $apruveSettings
            ->expects(static::once())
            ->method('getShortLabels')
            ->willReturn($shortLabelsCollection);

        $apruveSettings
            ->expects(static::once())
            ->method('getApiKey')
            ->willReturn($apiKey);

        $apruveSettings
            ->expects(static::once())
            ->method('getMerchantId')
            ->willReturn($merchantId);

        $apruveSettings
            ->expects(static::once())
            ->method('getTestMode')
            ->willReturn($testMode);

        $expectedSettings = new ApruveConfig(
            [
                ApruveConfig::ADMIN_LABEL_KEY => $channelName,
                ApruveConfig::LABEL_KEY => $label,
                ApruveConfig::SHORT_LABEL_KEY => $shortLabel,
                ApruveConfig::PAYMENT_METHOD_IDENTIFIER_KEY => $paymentMethodId,
                ApruveConfig::API_KEY_KEY => $apiKey,
                ApruveConfig::MERCHANT_ID_KEY => $merchantId,
                ApruveConfig::TEST_MODE_KEY => $testMode,
            ]
        );

        $actualSettings = $this->factory->create($apruveSettings);

        static::assertEquals($expectedSettings, $actualSettings);
    }

    /**
     * @return ApruveSettings|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createApruveSettingsMock()
    {
        return $this->createMock(ApruveSettings::class);
    }

    /**
     * @return Channel|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createChannelMock()
    {
        return $this->createMock(Channel::class);
    }

    /**
     * @return Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createLabelsCollectionMock()
    {
        return $this->createMock(Collection::class);
    }
}

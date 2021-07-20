<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\Config\Factory;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;
use Oro\Bundle\MoneyOrderBundle\Method\Config\Factory\MoneyOrderConfigFactory;
use Oro\Bundle\MoneyOrderBundle\Method\Config\Factory\MoneyOrderConfigFactoryInterface;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig;

class MoneyOrderConfigFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MoneyOrderConfigFactoryInterface
     */
    private $testedFactory;

    /**
     * @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $localizationHelperMock;

    /**
     * @var IntegrationIdentifierGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $integrationIdentifierGeneratorMock;

    protected function setUp(): void
    {
        $this->localizationHelperMock = $this->createMock(LocalizationHelper::class);
        $this->integrationIdentifierGeneratorMock = $this->createMock(IntegrationIdentifierGeneratorInterface::class);

        $this->testedFactory = new MoneyOrderConfigFactory(
            $this->localizationHelperMock,
            $this->integrationIdentifierGeneratorMock
        );
    }

    public function testCreate()
    {
        $channelName = 'someChannelName';
        $label = 'someLabel';
        $paymentMethodId = 'paymentMethodId';
        $payTo = 'payTo';
        $sendTo = 'sendTo';

        $paymentSettingsMock = $this->createMoneyOrderSettingsMock();
        $channelMock = $this->createChannelMock();
        $labelsCollection = $this->createLabelsCollectionMock();
        $shortLabelsCollection = $this->createLabelsCollectionMock();

        $this->integrationIdentifierGeneratorMock
            ->expects(static::once())
            ->method('generateIdentifier')
            ->with($channelMock)
            ->willReturn($paymentMethodId);

        $this->localizationHelperMock
            ->expects(static::at(0))
            ->method('getLocalizedValue')
            ->with($labelsCollection)
            ->willReturn($label);

        $this->localizationHelperMock
            ->expects(static::at(1))
            ->method('getLocalizedValue')
            ->with($shortLabelsCollection)
            ->willReturn($label);

        $channelMock
            ->expects(static::once())
            ->method('getName')
            ->willReturn($channelName);

        $paymentSettingsMock
            ->expects(static::once())
            ->method('getChannel')
            ->willReturn($channelMock);

        $paymentSettingsMock
            ->expects(static::once())
            ->method('getLabels')
            ->willReturn($labelsCollection);

        $paymentSettingsMock
            ->expects(static::once())
            ->method('getShortLabels')
            ->willReturn($shortLabelsCollection);

        $paymentSettingsMock
            ->expects(static::once())
            ->method('getPayTo')
            ->willReturn($payTo);

        $paymentSettingsMock
            ->expects(static::once())
            ->method('getSendTo')
            ->willReturn($sendTo);

        $expectedSettings = new MoneyOrderConfig(
            [
                MoneyOrderConfig::ADMIN_LABEL_KEY => $channelName,
                MoneyOrderConfig::LABEL_KEY => $label,
                MoneyOrderConfig::SHORT_LABEL_KEY => $label,
                MoneyOrderConfig::PAYMENT_METHOD_IDENTIFIER_KEY => $paymentMethodId,
                MoneyOrderConfig::PAY_TO_KEY => $payTo,
                MoneyOrderConfig::SEND_TO_KEY => $sendTo,
            ]
        );

        $actualSettings = $this->testedFactory->create($paymentSettingsMock);

        static::assertEquals($expectedSettings, $actualSettings);
    }

    /**
     * @return MoneyOrderSettings|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createMoneyOrderSettingsMock()
    {
        return $this->createMock(MoneyOrderSettings::class);
    }

    /**
     * @return Channel|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createChannelMock()
    {
        return $this->createMock(Channel::class);
    }

    /**
     * @return Collection|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createLabelsCollectionMock()
    {
        return $this->createMock(Collection::class);
    }
}

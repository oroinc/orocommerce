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
    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelperMock;

    /** @var IntegrationIdentifierGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $integrationIdentifierGeneratorMock;

    /** @var MoneyOrderConfigFactoryInterface */
    private $testedFactory;

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

        $paymentSettingsMock = $this->createMock(MoneyOrderSettings::class);
        $channelMock = $this->createMock(Channel::class);
        $labelsCollection = $this->createMock(Collection::class);
        $shortLabelsCollection = $this->createMock(Collection::class);

        $this->integrationIdentifierGeneratorMock->expects(self::once())
            ->method('generateIdentifier')
            ->with($channelMock)
            ->willReturn($paymentMethodId);

        $this->localizationHelperMock->expects(self::exactly(2))
            ->method('getLocalizedValue')
            ->withConsecutive([$labelsCollection], [$shortLabelsCollection])
            ->willReturnOnConsecutiveCalls($label, $label);

        $channelMock->expects(self::once())
            ->method('getName')
            ->willReturn($channelName);

        $paymentSettingsMock->expects(self::once())
            ->method('getChannel')
            ->willReturn($channelMock);
        $paymentSettingsMock->expects(self::once())
            ->method('getLabels')
            ->willReturn($labelsCollection);
        $paymentSettingsMock->expects(self::once())
            ->method('getShortLabels')
            ->willReturn($shortLabelsCollection);
        $paymentSettingsMock->expects(self::once())
            ->method('getPayTo')
            ->willReturn($payTo);
        $paymentSettingsMock->expects(self::once())
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

        self::assertEquals($expectedSettings, $actualSettings);
    }
}

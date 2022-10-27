<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method\Config\ParameterBag\Factory\Settings;

// @codingStandardsIgnoreStart
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;
use Oro\Bundle\PaymentTermBundle\Method\Config\ParameterBag\Factory\Settings\ParameterBagPaymentTermConfigBySettingsFactory;
use Oro\Bundle\PaymentTermBundle\Method\Config\ParameterBag\ParameterBagPaymentTermConfig;

// @codingStandardsIgnoreEnd

class ParameterBagPaymentTermConfigBySettingsFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var IntegrationIdentifierGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $integrationIdentifierGenerator;

    /** @var ParameterBagPaymentTermConfigBySettingsFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->integrationIdentifierGenerator = $this->createMock(IntegrationIdentifierGeneratorInterface::class);

        $this->factory = new ParameterBagPaymentTermConfigBySettingsFactory(
            $this->localizationHelper,
            $this->integrationIdentifierGenerator
        );
    }

    public function testCreateConfigBySettings()
    {
        $channelName = 'someChannelName';
        $label = 'someLabel';
        $paymentMethodId = 'paymentMethodId';

        $paymentSettings = $this->createMock(PaymentTermSettings::class);
        $channel = $this->createMock(Channel::class);
        $labelsCollection = $this->createMock(Collection::class);
        $shortLabelsCollection = $this->createMock(Collection::class);

        $this->integrationIdentifierGenerator->expects($this->once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn($paymentMethodId);

        $this->localizationHelper->expects($this->exactly(2))
            ->method('getLocalizedValue')
            ->withConsecutive([$labelsCollection], [$shortLabelsCollection])
            ->willReturnOnConsecutiveCalls($label, $label);

        $channel->expects($this->once())
            ->method('getName')
            ->willReturn($channelName);

        $paymentSettings->expects($this->once())
            ->method('getChannel')
            ->willReturn($channel);
        $paymentSettings->expects($this->once())
            ->method('getLabels')
            ->willReturn($labelsCollection);
        $paymentSettings->expects($this->once())
            ->method('getShortLabels')
            ->willReturn($shortLabelsCollection);

        $expectedSettings = new ParameterBagPaymentTermConfig(
            [
                ParameterBagPaymentTermConfig::FIELD_ADMIN_LABEL => $channelName,
                ParameterBagPaymentTermConfig::FIELD_LABEL => $label,
                ParameterBagPaymentTermConfig::FIELD_SHORT_LABEL => $label,
                ParameterBagPaymentTermConfig::FIELD_PAYMENT_METHOD_IDENTIFIER => $paymentMethodId
            ]
        );

        $actualSettings = $this->factory->createConfigBySettings($paymentSettings);

        $this->assertEquals($expectedSettings, $actualSettings);
    }
}

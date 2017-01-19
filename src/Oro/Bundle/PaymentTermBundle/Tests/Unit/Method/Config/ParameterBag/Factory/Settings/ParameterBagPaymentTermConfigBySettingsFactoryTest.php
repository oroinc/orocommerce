<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method\Config\ParameterBag\Factory\Settings;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;
use Oro\Bundle\PaymentTermBundle\Method\Config\ParameterBag\Factory\Settings\ParameterBagPaymentTermConfigBySettingsFactory;
use Oro\Bundle\PaymentTermBundle\Method\Config\ParameterBag\ParameterBagPaymentTermConfig;

class ParameterBagPaymentTermConfigBySettingsFactoryTest extends \PHPUnit_Framework_TestCase
{
    const payment_term_type = 'payment_term';

    /**
     * @var ParameterBagPaymentTermConfigBySettingsFactory
     */
    private $testedFactory;

    /**
     * @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $localizationHelperMock;

    /**
     * @var IntegrationIdentifierGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $integrationIdentifierGeneratorMock;

    public function setUp()
    {
        $this->localizationHelperMock = $this->createMock(LocalizationHelper::class);
        $this->integrationIdentifierGeneratorMock = $this->createMock(IntegrationIdentifierGeneratorInterface::class);

        $this->testedFactory = new ParameterBagPaymentTermConfigBySettingsFactory(
            $this->localizationHelperMock,
            $this->integrationIdentifierGeneratorMock
        );
    }

    public function testCreateConfigBySettings()
    {
        $channelName = 'someChannelName';
        $label = 'someLabel';
        $paymentMethodId = 'paymentMethodId';

        $paymentSettingsMock = $this->createPaymentTermSettingsMock();
        $channelMock = $this->createChannelMock();
        $labelsCollection = $this->createLabelsCollectionMock();
        $shortLabelsCollection = $this->createLabelsCollectionMock();

        $this->integrationIdentifierGeneratorMock
            ->expects($this->once())
            ->method('generateIdentifier')
            ->with($channelMock)
            ->willReturn($paymentMethodId);

        $this->localizationHelperMock
            ->expects($this->at(0))
            ->method('getLocalizedValue')
            ->with($labelsCollection)
            ->willReturn($label);

        $this->localizationHelperMock
            ->expects($this->at(1))
            ->method('getLocalizedValue')
            ->with($shortLabelsCollection)
            ->willReturn($label);

        $channelMock
            ->expects($this->once())
            ->method('getName')
            ->willReturn($channelName);

        $paymentSettingsMock
            ->expects($this->once())
            ->method('getChannel')
            ->willReturn($channelMock);

        $paymentSettingsMock
            ->expects($this->once())
            ->method('getLabels')
            ->willReturn($labelsCollection);

        $paymentSettingsMock
            ->expects($this->once())
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

        $actualSettings = $this->testedFactory->createConfigBySettings($paymentSettingsMock);

        $this->assertEquals($expectedSettings, $actualSettings);
    }

    /**
     * @return PaymentTermSettings|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createPaymentTermSettingsMock()
    {
        return $this->createMock(PaymentTermSettings::class);
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

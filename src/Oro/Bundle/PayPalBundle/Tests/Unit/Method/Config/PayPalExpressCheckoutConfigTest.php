<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PaymentBundle\Tests\Unit\Method\Config\AbstractPaymentConfigTestCase;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfig;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\ParameterBag;

class PayPalExpressCheckoutConfigTest extends AbstractPaymentConfigTestCase
{
    use EntityTrait;

    /** @var PayPalExpressCheckoutConfigInterface */
    protected $config;

    /**
     * {@inheritdoc}
     */
    protected function getPaymentConfig()
    {
        $label = (new LocalizedFallbackValue())->setString('test label');
        $labels = new ArrayCollection();
        $labels->add($label);

        $short_label = (new LocalizedFallbackValue())->setString('test short label');
        $short_labels = new ArrayCollection();
        $short_labels->add($short_label);

        $bag = [
            PayPalSettings::EXPRESS_CHECKOUT_LABELS_KEY =>$labels,
            PayPalSettings::EXPRESS_CHECKOUT_SHORT_LABELS_KEY => $short_labels,
            PayPalSettings::EXPRESS_CHECKOUT_PAYMENT_ACTION_KEY => 'paypal_payments_pro_express_payment_action',
            PayPalSettings::TEST_MODE_KEY => true,
        ];
        $settingsBag = $this->createMock(ParameterBag::class);
        $settingsBag->expects(static::any())->method('get')->willReturnCallback(
            function () use ($bag) {
                $args = func_get_args();
                return $bag[$args[0]];
            }
        );

        $transport = $this->createMock(Transport::class);
        $transport->expects(static::any())->method('getSettingsBag')->willReturn($settingsBag);

        /** @var Channel $channel */
        $channel = $this->getEntity(
            Channel::class,
            ['id' => 1, 'type' => 'paypal_payflow_gateway', 'transport' => $transport]
        );

        return new PayPalExpressCheckoutConfig(
            $channel,
            $this->encoder,
            $this->localizationHelper
        );
    }

    public function testIsTestMode()
    {
        $this->assertTrue($this->config->isTestMode());
    }

    public function testGetPurchaseAction()
    {
        $this->assertSame('paypal_payments_pro_express_payment_action', $this->config->getPurchaseAction());
    }
}

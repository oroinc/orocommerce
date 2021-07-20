<?php

namespace Oro\Bundle\PayPalBundle\Migrations\Data\ORM\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PayPalBundle\Settings\DataProvider\CreditCardTypesDataProviderInterface;
use Oro\Bundle\PayPalBundle\Settings\DataProvider\PaymentActionsDataProviderInterface;

class PayPalConfigFactory
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var CreditCardTypesDataProviderInterface
     */
    private $creditCardTypesDataProvider;

    /**
     * @var PaymentActionsDataProviderInterface
     */
    private $paymentActionsDataProvider;

    public function __construct(
        PaymentActionsDataProviderInterface $paymentActionsDataProvider,
        CreditCardTypesDataProviderInterface $creditCardTypesDataProvider,
        ConfigManager $configManager
    ) {
        $this->paymentActionsDataProvider = $paymentActionsDataProvider;
        $this->creditCardTypesDataProvider = $creditCardTypesDataProvider;
        $this->configManager = $configManager;
    }

    /**
     * @return PayPalConfig
     */
    public function createPaymentsProConfig()
    {
        return $this->createPayPalConfig(PayPalConfigKeysProviderFactory::createPaymentsProConfigKeyProvider());
    }

    /**
     * @return PayPalConfig
     */
    public function createPayflowGatewayConfig()
    {
        return $this->createPayPalConfig(PayPalConfigKeysProviderFactory::createPayflowGatewayConfigKeyProvider());
    }

    /**
     * @param PayPalConfigKeysProvider $keysProvider
     *
     * @return PayPalConfig
     */
    protected function createPayPalConfig(PayPalConfigKeysProvider $keysProvider)
    {
        return new PayPalConfig(
            $this->paymentActionsDataProvider,
            $this->creditCardTypesDataProvider,
            $this->configManager,
            $keysProvider
        );
    }
}

<?php

namespace Oro\Bundle\PayPalBundle\Migrations\Data\ORM\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PayPalBundle\Settings\DataProvider\CardTypesDataProviderInterface;
use Oro\Bundle\PayPalBundle\Settings\DataProvider\PaymentActionsDataProviderInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class PayPalConfigFactory
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var ManagerRegistry
     */
    private $cardTypesDataProvider;

    /**
     * @var ManagerRegistry
     */
    private $paymentActionsDataProvider;

    /**
     * @param PaymentActionsDataProviderInterface $paymentActionsDataProvider
     * @param CardTypesDataProviderInterface      $cardTypesDataProvider
     * @param ConfigManager                       $configManager
     */
    public function __construct(
        PaymentActionsDataProviderInterface $paymentActionsDataProvider,
        CardTypesDataProviderInterface $cardTypesDataProvider,
        ConfigManager $configManager
    ) {
        $this->paymentActionsDataProvider = $paymentActionsDataProvider;
        $this->cardTypesDataProvider = $cardTypesDataProvider;
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
            $this->cardTypesDataProvider,
            $this->configManager,
            $keysProvider
        );
    }
}

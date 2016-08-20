<?php

namespace Oro\Bundle\CheckoutBundle\DataProvider\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutLineItemsConverter;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Component\Checkout\DataProvider\CheckoutDataProviderInterface;

class CheckoutLineItemsManager
{
    /**
     * @var CheckoutDataProviderInterface[]
     */
    protected $providers = [];

    /**
     * @var CheckoutLineItemsConverter
     */
    protected $checkoutLineItemsConverter;

    /**
     * @var UserCurrencyManager
     */
    protected $userCurrencyManager;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param CheckoutLineItemsConverter $checkoutLineItemsConverter
     * @param UserCurrencyManager $UserCurrencyManager
     * @param ConfigManager $configManager
     */
    public function __construct(
        CheckoutLineItemsConverter $checkoutLineItemsConverter,
        UserCurrencyManager $UserCurrencyManager,
        ConfigManager $configManager
    ) {
        $this->checkoutLineItemsConverter = $checkoutLineItemsConverter;
        $this->userCurrencyManager = $UserCurrencyManager;
        $this->configManager = $configManager;
    }

    /**
     * @param CheckoutDataProviderInterface $provider
     */
    public function addProvider(CheckoutDataProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * @param CheckoutInterface $checkout
     * @param bool $disablePriceFilter
     * @return Collection|OrderLineItem[]
     */
    public function getData(CheckoutInterface $checkout, $disablePriceFilter = false)
    {
        $entity = $checkout->getSourceEntity();
        $currency = $this->userCurrencyManager->getUserCurrency();
        foreach ($this->providers as $provider) {
            if ($provider->isEntitySupported($entity)) {
                $supportedStatuses = $this->getSupportedStatuses();
                $lineItems = $this->checkoutLineItemsConverter->convert($provider->getData($entity));
                if (!$disablePriceFilter) {
                    $lineItems = $lineItems->filter(
                        function (OrderLineItem $lineItem) use ($currency, $supportedStatuses) {
                            $allowedProduct = true;

                            $product = $lineItem->getProduct();
                            if ($product) {
                                $allowedProduct = false;
                                if ($product->getInventoryStatus()) {
                                    $statusId = $product->getInventoryStatus()->getId();
                                    $allowedProduct = !empty($supportedStatuses[$statusId]);
                                }
                            }

                            return $allowedProduct
                                && (bool)$lineItem->getPrice()
                                && $lineItem->getPrice()->getCurrency() === $currency;
                        }
                    );
                }

                return $lineItems;
            }
        }

        return new ArrayCollection();
    }

    /**
     * @return array
     */
    protected function getSupportedStatuses()
    {
        $supportedStatuses = [];
        foreach ((array)$this->configManager->get('oro_b2b_order.frontend_product_visibility') as $status) {
            $supportedStatuses[$status] = true;
        }

        return $supportedStatuses;
    }
}

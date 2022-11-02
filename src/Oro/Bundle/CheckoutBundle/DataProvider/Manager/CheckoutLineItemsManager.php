<?php

namespace Oro\Bundle\CheckoutBundle\DataProvider\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderAwareTrait;
use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutLineItemsConverter;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Component\Checkout\DataProvider\CheckoutDataProviderInterface;

/**
 * This class provides obtaining Order line items from CheckoutInterface
 */
class CheckoutLineItemsManager
{
    use MemoryCacheProviderAwareTrait;

    /**
     * @var iterable|CheckoutDataProviderInterface[]
     */
    protected $providers;

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
     * @param iterable|CheckoutDataProviderInterface[] $providers
     * @param CheckoutLineItemsConverter $checkoutLineItemsConverter
     * @param UserCurrencyManager $userCurrencyManager
     * @param ConfigManager $configManager
     */
    public function __construct(
        iterable $providers,
        CheckoutLineItemsConverter $checkoutLineItemsConverter,
        UserCurrencyManager $userCurrencyManager,
        ConfigManager $configManager
    ) {
        $this->providers = $providers;
        $this->checkoutLineItemsConverter = $checkoutLineItemsConverter;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->configManager = $configManager;
    }

    /**
     * @param CheckoutInterface $checkout
     * @param bool $disablePriceFilter
     * @param string $configVisibilityPath
     * @return Collection|OrderLineItem[]
     */
    public function getData(
        CheckoutInterface $checkout,
        $disablePriceFilter = false,
        $configVisibilityPath = 'oro_order.frontend_product_visibility'
    ) {
        return $this->getMemoryCacheProvider()->get(
            ['checkout' => $checkout, $disablePriceFilter, $configVisibilityPath],
            function () use ($checkout, $disablePriceFilter, $configVisibilityPath) {
                return $this->getOrderLineItems($checkout, $disablePriceFilter, $configVisibilityPath);
            }
        );
    }

    protected function getOrderLineItems(
        CheckoutInterface $checkout,
        bool $disablePriceFilter = false,
        string $configVisibilityPath = 'oro_order.frontend_product_visibility'
    ): Collection {
        $lineItems = new ArrayCollection();
        $currency = $this->userCurrencyManager->getUserCurrency();
        $supportedStatuses = $this->getSupportedStatuses($configVisibilityPath);
        foreach ($this->providers as $provider) {
            if ($provider->isEntitySupported($checkout)) {
                $lineItems = $this->checkoutLineItemsConverter->convert($provider->getData($checkout));
                if (!$disablePriceFilter) {
                    $lineItems = $lineItems->filter(
                        function ($lineItem) use ($currency, $supportedStatuses) {
                            return $this->isLineItemHasCurrencyAndSupportedStatus(
                                $lineItem,
                                $currency,
                                $supportedStatuses
                            );
                        }
                    );
                }

                break;
            }
        }

        return $lineItems;
    }

    /**
     * @param CheckoutInterface $checkout
     * @return Collection|OrderLineItem[]
     */
    public function getLineItemsWithoutQuantity(CheckoutInterface $checkout)
    {
        $lineItems = $this->getData($checkout, true);
        $lineItemsWithoutQuantity = new ArrayCollection();

        foreach ($lineItems as $key => $lineItem) {
            // quantity == 0
            if (abs($lineItem->getQuantity()) <= 1e-6) {
                $lineItemsWithoutQuantity->add($lineItem);
            }
        }

        return $lineItemsWithoutQuantity;
    }

    /**
     * @param string $configVisibilityPath
     * @return array
     */
    protected function getSupportedStatuses($configVisibilityPath)
    {
        $supportedStatuses = [];
        foreach ((array)$this->configManager->get($configVisibilityPath) as $status) {
            $supportedStatuses[$status] = true;
        }

        return $supportedStatuses;
    }

    /**
     * @param object $lineItem
     * @param string $currency
     * @param array  $supportedStatuses
     * @return bool
     */
    protected function isLineItemHasCurrencyAndSupportedStatus($lineItem, $currency, array $supportedStatuses)
    {
        if (!$lineItem instanceof ProductHolderInterface || !$lineItem instanceof PriceAwareInterface) {
            return false;
        }
        $allowedProduct = true;

        $product = $lineItem->getProduct();
        if ($product) {
            $allowedProduct = false;
            if ($product->getInventoryStatus()) {
                $statusId = $product->getInventoryStatus()->getId();
                $allowedProduct = !empty($supportedStatuses[$statusId]);
            }
        }

        $lineItemPrice = $lineItem->getPrice();

        return $allowedProduct
            && (bool)$lineItemPrice
            && $lineItemPrice->getCurrency() === $currency;
    }
}

<?php

namespace Oro\Bundle\CheckoutBundle\DataProvider\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
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
    /** The line item is accepted for the order. */
    public const string REASON_SUPPORTED = 'supported';
    /** The line item was skipped because its price currency differs from the active checkout currency. */
    public const string REASON_CURRENCY_MISMATCH = 'currency_mismatch';
    /** The line item was skipped because it has no price. */
    public const string REASON_NO_PRICE = 'no_price';
    /** The line item was skipped because its product inventory status is not supported for checkout. */
    public const string REASON_UNSUPPORTED_STATUS = 'unsupported_inventory_status';

    /** @var iterable|CheckoutDataProviderInterface[] */
    protected iterable $providers;
    protected CheckoutLineItemsConverter $checkoutLineItemsConverter;
    protected UserCurrencyManager $userCurrencyManager;
    protected ConfigManager $configManager;
    private MemoryCacheProviderInterface $memoryCacheProvider;

    /**
     * @param iterable|CheckoutDataProviderInterface[] $providers
     * @param CheckoutLineItemsConverter $checkoutLineItemsConverter
     * @param UserCurrencyManager $userCurrencyManager
     * @param ConfigManager $configManager
     * @param MemoryCacheProviderInterface $memoryCacheProvider
     */
    public function __construct(
        iterable $providers,
        CheckoutLineItemsConverter $checkoutLineItemsConverter,
        UserCurrencyManager $userCurrencyManager,
        ConfigManager $configManager,
        MemoryCacheProviderInterface $memoryCacheProvider
    ) {
        $this->providers = $providers;
        $this->checkoutLineItemsConverter = $checkoutLineItemsConverter;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->configManager = $configManager;
        $this->memoryCacheProvider = $memoryCacheProvider;
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
        return $this->memoryCacheProvider->get(
            ['checkout' => $checkout, $disablePriceFilter, $configVisibilityPath, uniqid()],
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
                        fn ($lineItem) => $this->getLineItemFilterReason($lineItem, $currency, $supportedStatuses)
                            === self::REASON_SUPPORTED
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
     * @return string
     */
    public function getLineItemFilterReason($lineItem, $currency, array $supportedStatuses): string
    {
        if (!$lineItem instanceof ProductHolderInterface || !$lineItem instanceof PriceAwareInterface) {
            return self::REASON_UNSUPPORTED_STATUS;
        }

        $product = $lineItem->getProduct();
        if ($product) {
            $inventoryStatus = $product->getInventoryStatus();
            if (!$inventoryStatus || empty($supportedStatuses[$inventoryStatus->getId()])) {
                return self::REASON_UNSUPPORTED_STATUS;
            }
        }

        $lineItemPrice = $lineItem->getPrice();
        if (!$lineItemPrice) {
            return self::REASON_NO_PRICE;
        }

        if ($lineItemPrice->getCurrency() !== $currency) {
            return self::REASON_CURRENCY_MISMATCH;
        }

        return self::REASON_SUPPORTED;
    }

    /**
     * Same as getData(), but also reports the reasons (REASON_* constants) the line items were skipped from
     * the order under the active currency.
     *
     * @return array{items: Collection, skippedReasons: string[]}
     */
    public function getDataWithReason(
        CheckoutInterface $checkout,
        bool $disablePriceFilter = false,
        string $configVisibilityPath = 'oro_order.frontend_product_visibility'
    ): array {
        $currency = $this->userCurrencyManager->getUserCurrency();
        $supportedStatuses = $this->getSupportedStatuses($configVisibilityPath);
        $items = new ArrayCollection();
        $skippedReasons = [];
        foreach ($this->providers as $provider) {
            if ($provider->isEntitySupported($checkout)) {
                foreach ($this->checkoutLineItemsConverter->convert($provider->getData($checkout)) as $lineItem) {
                    if ($disablePriceFilter) {
                        $items->add($lineItem);
                        continue;
                    }

                    $reason = $this->getLineItemFilterReason($lineItem, $currency, $supportedStatuses);
                    if ($reason === self::REASON_SUPPORTED) {
                        $items->add($lineItem);
                    } else {
                        $skippedReasons[$reason] = true;
                    }
                }

                break;
            }
        }

        return ['items' => $items, 'skippedReasons' => array_keys($skippedReasons)];
    }
}

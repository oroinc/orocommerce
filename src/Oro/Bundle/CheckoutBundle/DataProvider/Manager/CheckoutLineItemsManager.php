<?php

namespace Oro\Bundle\CheckoutBundle\DataProvider\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutLineItemsConverter;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Component\Checkout\DataProvider\CheckoutDataProviderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * This class provides obtaining Order line items from CheckoutInterface
 */
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
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @param CheckoutLineItemsConverter $checkoutLineItemsConverter
     * @param UserCurrencyManager $userCurrencyManager
     * @param ConfigManager $configManager
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(
        CheckoutLineItemsConverter $checkoutLineItemsConverter,
        UserCurrencyManager $userCurrencyManager,
        ConfigManager $configManager,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->checkoutLineItemsConverter = $checkoutLineItemsConverter;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->configManager = $configManager;
        $this->authorizationChecker = $authorizationChecker;
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
     * @param string $configVisibilityPath
     * @return Collection|OrderLineItem[]
     */
    public function getData(
        CheckoutInterface $checkout,
        $disablePriceFilter = false,
        $configVisibilityPath = 'oro_order.frontend_product_visibility'
    ) {
        foreach ($this->providers as $provider) {
            if ($provider->isEntitySupported($checkout)) {
                $lineItems = $this->checkoutLineItemsConverter->convert($provider->getData($checkout));
                if (!$disablePriceFilter) {
                    $currency = $this->userCurrencyManager->getUserCurrency();
                    $supportedStatuses = $this->getSupportedStatuses($configVisibilityPath);
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

                return $lineItems;
            }
        }

        return new ArrayCollection();
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
     * @return bool
     */
    protected function isLineItemAvailable($lineItem)
    {
        if (!$lineItem instanceof ProductHolderInterface) {
            return true;
        }

        $product = $lineItem->getProduct();
        if (!$product) {
            return true;
        }

        $isAvailable = $product->getStatus() === Product::STATUS_ENABLED
            && $this->authorizationChecker->isGranted('VIEW', $product);

        return $isAvailable;
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

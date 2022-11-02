<?php

namespace Oro\Bundle\CheckoutBundle\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Model\CheckoutLineItemConverterInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\InventoryBundle\Provider\InventoryQuantityProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Converts Order line items to CheckoutLineItems.
 */
class OrderLineItemConverter implements CheckoutLineItemConverterInterface
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var InventoryQuantityProviderInterface */
    protected $quantityProvider;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var string */
    protected $configPath;

    /** @var EntityFallbackResolver */
    protected $entityFallbackResolver;

    /**
     * @param ConfigManager $configManager
     * @param InventoryQuantityProviderInterface $quantityProvider
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param EntityFallbackResolver $entityFallbackResolver
     * @param string $configPath
     */
    public function __construct(
        ConfigManager $configManager,
        InventoryQuantityProviderInterface $quantityProvider,
        AuthorizationCheckerInterface $authorizationChecker,
        EntityFallbackResolver $entityFallbackResolver,
        $configPath
    ) {
        $this->configManager = $configManager;
        $this->quantityProvider = $quantityProvider;
        $this->authorizationChecker = $authorizationChecker;
        $this->entityFallbackResolver = $entityFallbackResolver;
        $this->configPath = $configPath;
    }

    /**
     * {@inheritDoc}
     */
    public function isSourceSupported($source)
    {
        return $source instanceof Order;
    }

    /**
     * @param Order $source
     * {@inheritDoc}
     */
    public function convert($source)
    {
        $lineItems = $source->getLineItems();
        $checkoutLineItems = new ArrayCollection();

        foreach ($lineItems as $lineItem) {
            if (!$this->isLineItemAvailable($lineItem)) {
                continue;
            }

            $availableQuantity = $this->getAvailableProductQuantity($lineItem);
            if ($availableQuantity <= 0) {
                continue;
            }

            $checkoutLineItem = new CheckoutLineItem();
            $checkoutLineItem
                ->setFromExternalSource(false)
                ->setPriceFixed(false)
                ->setProduct($lineItem->getProduct())
                ->setParentProduct($lineItem->getParentProduct())
                ->setFreeFormProduct($lineItem->getFreeFormProduct())
                ->setProductSku($lineItem->getProductSku())
                ->setProductUnit($lineItem->getProductUnit())
                ->setProductUnitCode($lineItem->getProductUnitCode())
                // use only available quantity of the product
                ->setQuantity(min($availableQuantity, $lineItem->getQuantity()))
                ->setComment($lineItem->getComment());

            $checkoutLineItems->add($checkoutLineItem);
        }

        return $checkoutLineItems;
    }

    /**
     * @param OrderLineItem $lineItem
     * @return bool
     */
    protected function isLineItemAvailable(OrderLineItem $lineItem)
    {
        $product = $lineItem->getProduct();

        if (!$product || !$lineItem->getProductUnit()) {
            return false;
        }

        if ($product->getStatus() !== Product::STATUS_ENABLED) {
            return false;
        }

        $inventoryStatuses = $this->configManager->get($this->configPath);

        if (!in_array($product->getInventoryStatus()->getId(), $inventoryStatuses, true)) {
            return false;
        }

        return $this->authorizationChecker->isGranted('VIEW', $lineItem->getProduct());
    }

    /**
     * @param ProductLineItemInterface $lineItem
     * @return int
     */
    protected function getAvailableProductQuantity(ProductLineItemInterface $lineItem)
    {
        if ($lineItem->getProduct()
            && $this->entityFallbackResolver->getFallbackValue($lineItem->getProduct(), 'backOrder')
        ) {
            return $lineItem->getQuantity();
        }

        if (!$this->quantityProvider->canDecrement($lineItem->getProduct())) {
            return $lineItem->getQuantity();
        }

        return $this->quantityProvider->getAvailableQuantity($lineItem->getProduct(), $lineItem->getProductUnit());
    }
}

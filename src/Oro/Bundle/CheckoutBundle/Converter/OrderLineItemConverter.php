<?php

namespace Oro\Bundle\CheckoutBundle\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Model\CheckoutLineItemConverterInterface;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutValidationGroupsBySourceEntityProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\InventoryBundle\Provider\InventoryQuantityProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    /** @var ProductKitItemLineItemConverter */
    protected $productKitItemLineItemConverter;

    /** @var ValidatorInterface */
    protected $validator;

    /** @var CheckoutValidationGroupsBySourceEntityProvider */
    protected $validationGroupsProvider;

    /** @var array<string|array<string>>  */
    protected array $validationGroups = [['Default', 'order_line_item_to_checkout_line_item_convert']];

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
        $this->productKitItemLineItemConverter = new ProductKitItemLineItemConverter();
        $this->configPath = $configPath;
    }

    public function setProductKitItemLineItemConverter(
        ProductKitItemLineItemConverter $productKitItemLineItemConverter
    ): void {
        $this->productKitItemLineItemConverter = $productKitItemLineItemConverter;
    }

    public function setValidator(ValidatorInterface $validator): void
    {
        $this->validator = $validator;
    }

    public function setValidationGroupsProvider(
        CheckoutValidationGroupsBySourceEntityProvider $validationGroupsProvider
    ): void {
        $this->validationGroupsProvider = $validationGroupsProvider;
    }

    /**
     * @param array<string> $validationGroups
     */
    public function setValidationGroups(array $validationGroups): void
    {
        $this->validationGroups = $validationGroups;
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

        $isValidateLineItem = !$this->validator || !$this->validationGroupsProvider;
        foreach ($lineItems as $lineItem) {
            if ($isValidateLineItem && !$this->isLineItemAvailable($lineItem)) {
                continue;
            }

            $availableQuantity = $this->getAvailableProductQuantity($lineItem);
            if ($availableQuantity <= 0) {
                continue;
            }

            $checkoutLineItem = (new CheckoutLineItem())
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
                ->setComment($lineItem->getComment())
                ->setChecksum($lineItem->getChecksum());

            foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
                $checkoutLineItem->addKitItemLineItem(
                    $this->productKitItemLineItemConverter->convert($kitItemLineItem)
                );
            }

            $checkoutLineItems->add($checkoutLineItem);
        }

        return $isValidateLineItem ? $checkoutLineItems : $this->getValidLineItems($checkoutLineItems);
    }

    /**
     * @param OrderLineItem $lineItem
     * @return bool
     *
     * @deprecated since 5.1, use getValidLineItems instead
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
     * @param Collection<CheckoutLineItem> $lineItems
     *
     * @return Collection<CheckoutLineItem>
     */
    private function getValidLineItems(Collection $lineItems): Collection
    {
        if (!$lineItems->count()) {
            return $lineItems;
        }

        $validationGroups = $this->validationGroupsProvider
            ->getValidationGroupsBySourceEntity($this->validationGroups, OrderLineItem::class);

        $violationList = $this->validator->validate($lineItems, null, $validationGroups);
        foreach ($violationList as $violation) {
            if (!$violation->getPropertyPath()) {
                continue;
            }

            $propertyPath = new PropertyPath($violation->getPropertyPath());
            if (!$propertyPath->isIndex(0)) {
                continue;
            }

            $index = $propertyPath->getElement(0);
            $lineItems->remove($index);
        }

        return $lineItems;
    }

    /**
     * @param ProductLineItemInterface $lineItem
     * @return int
     */
    protected function getAvailableProductQuantity(ProductLineItemInterface $lineItem)
    {
        $product = $lineItem->getProduct();
        if (!$product) {
            return 0;
        }

        if ($this->entityFallbackResolver->getFallbackValue($product, 'backOrder')) {
            return $lineItem->getQuantity();
        }

        if (!$this->quantityProvider->canDecrement($product)) {
            return $lineItem->getQuantity();
        }

        $productUnit = $lineItem->getProductUnit();

        return $productUnit ? $this->quantityProvider->getAvailableQuantity($product, $productUnit) : 0;
    }
}

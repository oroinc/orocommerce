<?php

namespace Oro\Bundle\SaleBundle\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Converter\ProductKitItemLineItemConverter;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Model\CheckoutLineItemConverterInterface;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutValidationGroupsBySourceEntityProvider;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Converts QuoteDemand line items to CheckoutLineItems.
 */
class QuoteDemandLineItemConverter implements CheckoutLineItemConverterInterface
{
    /** @var array<string|array<string>>  */
    protected array $validationGroups = [['Default', 'quote_demand_line_item_to_checkout_line_item_convert']];

    private ProductKitItemLineItemConverter $productKitItemLineItemConverter;

    private ValidatorInterface $validator;

    private CheckoutValidationGroupsBySourceEntityProvider $validationGroupsProvider;

    public function __construct(
        ProductKitItemLineItemConverter $productKitItemLineItemConverter,
        ValidatorInterface $validator,
        CheckoutValidationGroupsBySourceEntityProvider $validationGroupsProvider
    ) {
        $this->productKitItemLineItemConverter = $productKitItemLineItemConverter;
        $this->validator = $validator;
        $this->validationGroupsProvider = $validationGroupsProvider;
    }

    /**
     * @param array<string> $validationGroups
     */
    public function setValidationGroups(array $validationGroups): void
    {
        $this->validationGroups = $validationGroups;
    }

    #[\Override]
    public function isSourceSupported($source)
    {
        return $source instanceof QuoteDemand;
    }

    /**
     * @param QuoteDemand $source
     */
    #[\Override]
    public function convert($source)
    {
        $lineItems = $source->getLineItems();
        $checkoutLineItems = new ArrayCollection();

        foreach ($lineItems as $lineItem) {
            $quoteProductOffer = $lineItem->getQuoteProductOffer();
            $productSku = $quoteProductOffer->getProductSku()
                ??  $quoteProductOffer->getQuoteProduct()->getProductSku();
            $freeFormProduct = !$quoteProductOffer->getQuoteProduct()->getProduct()
                ? $quoteProductOffer->getQuoteProduct()->getFreeFormProduct()
                : null;

            $checkoutLineItem = new CheckoutLineItem();
            $checkoutLineItem
                ->setFromExternalSource(true)
                ->setPriceFixed(true)
                ->setProduct($quoteProductOffer->getProduct())
                ->setParentProduct($quoteProductOffer->getParentProduct())
                ->setFreeFormProduct($freeFormProduct)
                ->setProductSku($productSku)
                ->setProductUnit($quoteProductOffer->getProductUnit())
                ->setProductUnitCode($quoteProductOffer->getProductUnitCode())
                ->setQuantity($lineItem->getQuantity())
                ->setPrice($quoteProductOffer->getPrice())
                ->setPriceType($quoteProductOffer->getPriceType())
                ->setComment($quoteProductOffer->getQuoteProduct()->getComment())
                ->setChecksum($lineItem->getChecksum());

            foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
                $checkoutLineItem->addKitItemLineItem(
                    $this->productKitItemLineItemConverter->convert($kitItemLineItem)
                );
            }

            $checkoutLineItems->add($checkoutLineItem);
        }

        return $this->getValidLineItems($checkoutLineItems);
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
            ->getValidationGroupsBySourceEntity($this->validationGroups, QuoteProductDemand::class);

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
}

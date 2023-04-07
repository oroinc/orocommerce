<?php

namespace Oro\Bundle\SaleBundle\Form\Extension;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Extension\AbstractProductDataStorageExtension;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType;

/**
 * The form type extension that pre-fill an quote with requested products taken from the product data storage.
 */
class QuoteDataStorageExtension extends AbstractProductDataStorageExtension
{
    /**
     * {@inheritDoc}
     */
    protected function addItem(Product $product, object $entity, array $itemData): void
    {
        /** @var Quote $entity */

        $quoteProduct = new QuoteProduct();
        $quoteProduct->setProduct($product);

        $this->fillEntityData($quoteProduct, $itemData);

        if (!empty($itemData['requestProductItems'])) {
            $this->addItems($product, $quoteProduct, $itemData['requestProductItems']);
        }

        $entity->addQuoteProduct($quoteProduct);
    }

    private function addItems(Product $product, QuoteProduct $quoteProduct, array $itemsData): void
    {
        $defaultUnit = $this->getDefaultProductUnit($product);

        foreach ($itemsData as $subItemData) {
            $quoteProductRequest = new QuoteProductRequest();
            $quoteProductOffer = new QuoteProductOffer();

            $quoteProductOffer->setAllowIncrements(true);

            $this->fillEntityData($quoteProductRequest, $subItemData);
            $this->fillEntityData($quoteProductOffer, $subItemData);

            if (null === $defaultUnit && !$quoteProductRequest->getProductUnit()) {
                continue;
            }

            if (!$quoteProductRequest->getProductUnit()) {
                $quoteProductRequest->setProductUnit($defaultUnit);
                $quoteProductOffer->setProductUnit($defaultUnit);
            }

            $quoteProduct->addQuoteProductRequest($quoteProductRequest);
            $quoteProduct->addQuoteProductOffer($quoteProductOffer);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getEntityClass(): string
    {
        return Quote::class;
    }

    /**
     * {@inheritDoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [QuoteType::class];
    }
}

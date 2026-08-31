<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ParameterBagInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets a product for a quote product if it was not submitted
 * but the quote product has a product SKU and the quote product does not represent a free form entry.
 */
class UpdateQuoteProductProduct implements ProcessorInterface
{
    private const PRODUCT_IDS = 'quote_product_product_ids';

    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly AclHelper $aclHelper
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $productSkuForm = $context->findFormField('productSku');
        if (null === $productSkuForm || !$productSkuForm->isSubmitted()) {
            return;
        }

        $productForm = $context->findFormField('product');
        if (null !== $productForm && $productForm->isSubmitted()) {
            return;
        }

        $this->updateProduct($context->getData(), $context->getSharedData(), $context->getIncludedEntities());
    }

    private function updateProduct(
        QuoteProduct $quoteProduct,
        ParameterBagInterface $sharedData,
        ?IncludedEntityCollection $includedEntities
    ): void {
        if ($this->isApplicableQuoteProduct($quoteProduct)) {
            $this->ensureProductIdsInitialized($sharedData, $includedEntities);
            $product = $this->getProduct($this->getProductSku($quoteProduct), $sharedData);
            if (null !== $product) {
                $quoteProduct->setProduct($product);
            }
        }
    }

    private function isApplicableQuoteProduct(QuoteProduct $quoteProduct): bool
    {
        return
            $quoteProduct->getProductSku()
            && !$quoteProduct->getFreeFormProduct()
            && null === $quoteProduct->getProduct();
    }

    private function getProductSku(QuoteProduct $quoteProduct): string
    {
        return mb_strtoupper($quoteProduct->getProductSku());
    }

    private function ensureProductIdsInitialized(
        ParameterBagInterface $sharedData,
        ?IncludedEntityCollection $includedEntities
    ): void {
        if (!$sharedData->has(self::PRODUCT_IDS)) {
            $productIds = [];
            if (null !== $includedEntities) {
                $productIds = $this->getProductIds($includedEntities);
            }
            $sharedData->set(self::PRODUCT_IDS, $productIds);
        }
    }

    private function getProduct(string $productSku, ParameterBagInterface $sharedData): ?Product
    {
        $productIds = $sharedData->get(self::PRODUCT_IDS);
        if (\array_key_exists($productSku, $productIds)) {
            $productId = $productIds[$productSku];
        } else {
            $productId = $this->loadProductId($productSku);
            $productIds[$productSku] = $productId;
            $sharedData->set(self::PRODUCT_IDS, $productIds);
        }

        if (null === $productId) {
            return null;
        }

        return $this->doctrineHelper
            ->getEntityManagerForClass(Product::class)
            ->getReference(Product::class, $productId);
    }

    /**
     * @param IncludedEntityCollection $includedEntities
     *
     * @return array [product sku => product id or NULL, ....]
     */
    private function getProductIds(IncludedEntityCollection $includedEntities): array
    {
        $productSkus = [];
        $entity = $includedEntities->getPrimaryEntity();
        if ($entity instanceof QuoteProduct && $this->isApplicableQuoteProduct($entity)) {
            $productSkus[] = $this->getProductSku($entity);
        }
        foreach ($includedEntities as $entity) {
            if ($entity instanceof QuoteProduct && $this->isApplicableQuoteProduct($entity)) {
                $productSkus[] = $this->getProductSku($entity);
            }
        }

        return $this->loadProductIds(array_values(array_unique($productSkus)));
    }

    private function loadProductId(string $productSku): ?int
    {
        $productIds = $this->loadProductIds([$productSku]);

        return $productIds[$productSku];
    }

    /**
     * @param string[] $productSkus
     *
     * @return array [product sku => product id or NULL, ....]
     */
    private function loadProductIds(array $productSkus): array
    {
        $qb = $this->doctrineHelper
            ->createQueryBuilder(Product::class, 'p')
            ->select('p.id, p.skuUppercase AS sku')
            ->where('p.skuUppercase IN (:skus)')
            ->setParameter('skus', $productSkus);
        $rows = $this->aclHelper->apply($qb)->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['sku']] = $row['id'];
        }
        foreach ($productSkus as $sku) {
            if (!\array_key_exists($sku, $result)) {
                $result[$sku] = null;
            }
        }

        return $result;
    }
}

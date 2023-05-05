<?php

namespace Oro\Bundle\ProductBundle\Handler;

use Oro\Bundle\EntityBundle\Handler\AbstractEntityDeleteHandlerExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductKitsByProductProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Delete handler extension for Product entity.
 */
class ProductDeleteHandlerExtension extends AbstractEntityDeleteHandlerExtension
{
    private ProductKitsByProductProvider $productKitsByProductProvider;

    private TranslatorInterface $translator;

    public function __construct(
        ProductKitsByProductProvider $productKitsByProductProvider,
        TranslatorInterface $translator
    ) {
        $this->productKitsByProductProvider = $productKitsByProductProvider;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function assertDeleteGranted($entity): void
    {
        if (!$entity instanceof Product) {
            return;
        }

        $productsSkus = $this->productKitsByProductProvider->getRelatedProductKitsSku($entity);
        if (!$productsSkus) {
            // Skips further execution as the product is not referenced by any product kit items.
            return;
        }

        throw $this->createAccessDeniedException(
            $this->translator->trans(
                'oro.product.referenced_by_product_kits',
                [
                    '{{ product_sku }}' => $entity->getSku(),
                    '{{ product_kits_skus }}' => implode(', ', $productsSkus),
                ],
                'validators'
            )
        );
    }
}

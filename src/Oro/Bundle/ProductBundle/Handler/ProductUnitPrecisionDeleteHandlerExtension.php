<?php

namespace Oro\Bundle\ProductBundle\Handler;

use Oro\Bundle\EntityBundle\Handler\AbstractEntityDeleteHandlerExtension;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\ProductKitsByUnitPrecisionProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Delete handler extension for ProductUnitPrecision entity.
 */
class ProductUnitPrecisionDeleteHandlerExtension extends AbstractEntityDeleteHandlerExtension
{
    private ProductKitsByUnitPrecisionProvider $productKitsByUnitPrecisionProvider;

    private TranslatorInterface $translator;

    public function __construct(
        ProductKitsByUnitPrecisionProvider $productKitsByUnitPrecisionProvider,
        TranslatorInterface $translator
    ) {
        $this->productKitsByUnitPrecisionProvider = $productKitsByUnitPrecisionProvider;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function assertDeleteGranted($entity): void
    {
        if (!$entity instanceof ProductUnitPrecision) {
            return;
        }

        $product = $entity->getProduct();
        if (null === $product) {
            return;
        }

        $primaryProductUnitPrecision = $product->getPrimaryUnitPrecision();
        if (null !== $primaryProductUnitPrecision && $primaryProductUnitPrecision->getId() === $entity->getId()) {
            throw $this->createAccessDeniedException('primary precision');
        }

        $productsSkus = $this->productKitsByUnitPrecisionProvider->getRelatedProductKitsSku($entity);
        if ($productsSkus) {
            throw $this->createAccessDeniedException(
                $this->translator->trans(
                    'oro.product.unit_precisions_items.referenced_by_product_kits',
                    [
                        '{{ product_unit }}' => $entity->getProductUnitCode(),
                        '{{ product_kits_skus }}' => implode(', ', $productsSkus),
                    ],
                    'validators'
                )
            );
        }
    }
}

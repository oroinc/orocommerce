<?php

namespace Oro\Bundle\ProductBundle\Handler;

use Oro\Bundle\EntityBundle\Handler\AbstractEntityDeleteHandlerExtension;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductKitItemRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Delete handler extension for ProductKitItem entity.
 */
class ProductKitItemDeleteHandlerExtension extends AbstractEntityDeleteHandlerExtension
{
    private TranslatorInterface $translator;

    private LocalizationHelper $localizationHelper;

    public function __construct(
        LocalizationHelper $localizationHelper,
        TranslatorInterface $translator
    ) {
        $this->localizationHelper = $localizationHelper;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function assertDeleteGranted($entity): void
    {
        if (!$entity instanceof ProductKitItem) {
            return;
        }

        /** @var Product $productKit */
        $productKit = $entity->getProductKit();
        /** @var ProductKitItemRepository $productKitItemRepository */
        $productKitItemRepository = $this->getEntityRepository(ProductKitItem::class);
        if ($productKitItemRepository->getKitItemsCount($productKit->getId()) <= 1) {
            // Throws exception as the product kit item is the last one belonging to the product kit.
            throw $this->createAccessDeniedException(
                $this->translator->trans(
                    'oro.product.kit_items.last_one',
                    [
                        '{{ kit_item_label }}' => $this->localizationHelper->getLocalizedValue($entity->getLabels()),
                        '{{ product_kit_sku }}' => $productKit->getSku(),
                    ],
                    'validators'
                )
            );
        }
    }
}

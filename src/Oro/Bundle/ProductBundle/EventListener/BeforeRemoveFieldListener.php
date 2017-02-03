<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Event\BeforeRemoveFieldEvent;
use Oro\Bundle\ProductBundle\Entity\Product;

use Symfony\Component\Translation\TranslatorInterface;

class BeforeRemoveFieldListener
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(DoctrineHelper $doctrineHelper, TranslatorInterface $translator)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->translator = $translator;
    }

    /**
     * @param BeforeRemoveFieldEvent $event
     */
    public function onBeforeRemoveField(BeforeRemoveFieldEvent $event)
    {
        if ($event->getClassName() !== Product::class && !is_subclass_of($event->getClassName(), Product::class)) {
            return;
        }

        $fieldName = $event->getFieldName();
        $skuList = [];

        /** @var Product[] $configurableProducts */
        $configurableProducts = $this->doctrineHelper->getEntityRepository(Product::class)->findBy([
            'type' => Product::TYPE_CONFIGURABLE
        ]);

        foreach ($configurableProducts as $configurableProduct) {
            if (in_array($fieldName, $configurableProduct->getVariantFields())) {
                $skuList[] = $configurableProduct->getSku();
            }
        }

        if ($skuList) {
            $event->addValidationMessage(
                $this->translator->trans('oro.product.field_is_used_as_variant_field.message', [
                    '%skuList%' => implode(', ', array_unique($skuList)),
                ])
            );
        }
    }
}

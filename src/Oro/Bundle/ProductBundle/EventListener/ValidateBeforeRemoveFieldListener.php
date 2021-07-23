<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Event\ValidateBeforeRemoveFieldEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Contracts\Translation\TranslatorInterface;

class ValidateBeforeRemoveFieldListener
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var TranslatorInterface */
    protected $translator;

    public function __construct(DoctrineHelper $doctrineHelper, TranslatorInterface $translator)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->translator = $translator;
    }

    public function onValidateBeforeRemoveField(ValidateBeforeRemoveFieldEvent $event)
    {
        $field = $event->getFieldConfig();

        if (!is_a($field->getEntity()->getClassName(), Product::class, true)) {
            return;
        }

        $fieldName = $field->getFieldName();
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

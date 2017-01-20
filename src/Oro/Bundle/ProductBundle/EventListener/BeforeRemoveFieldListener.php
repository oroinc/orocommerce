<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Event\BeforeRemoveFieldEvent;
use Oro\Bundle\ProductBundle\Entity\Product;

class BeforeRemoveFieldListener
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
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
                $event->setHasErrors(true);
                $skuList[] = $configurableProduct->getSku();
            }
        }

        if ($event->hasErrors()) {
            $event->setValidationMessage(sprintf(
                '%s%s',
                'Cannot remove field because it\'s used as a variant field in the following configurable products: ',
                implode(', ', array_unique($skuList))
            ));
        }
    }
}

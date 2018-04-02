<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class ProductFormViewListener extends AbstractFormViewListener
{
    /**
     * {@inheritdoc}
     */
    public function onView(BeforeListRenderEvent $event)
    {
        /** @var Product $product */
        $product = $this->getEntityFromRequest();
        if (!$product) {
            return;
        }

        $entity = $product->getTaxCode();

        $template = $event->getEnvironment()->render(
            'OroTaxBundle:Product:tax_code_view.html.twig',
            ['entity' => $entity]
        );
        $event->getScrollData()->addSubBlockData('general', 0, $template);
    }

    /**
     * {@inheritdoc}
     */
    public function onEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroTaxBundle:Product:tax_code_update.html.twig',
            ['form' => $event->getFormView()]
        );

        $scrollData = $event->getScrollData();

        $blockIds = $scrollData->getBlockIds();
        $firstBlockId = reset($blockIds);
        $subblockIds = $scrollData->getSubblockIds($firstBlockId);
        $firstSubBlockId = reset($subblockIds);

        $event->getScrollData()->addSubBlockData($firstBlockId, $firstSubBlockId, $template);
    }
}

<?php

namespace OroB2B\Bundle\TaxBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository;

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

        /** @var ProductTaxCodeRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository($this->taxCodeClass);
        $entity = $repository->findOneByProduct($product);

        $template = $event->getEnvironment()->render(
            'OroB2BTaxBundle:Product:tax_code_view.html.twig',
            ['entity' => $entity]
        );
        $this->addTaxCodeBlock($event->getScrollData(), $template, 'orob2b.tax.product.section.taxes');
    }

    /**
     * {@inheritdoc}
     */
    public function onEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroB2BTaxBundle:Product:tax_code_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $this->addTaxCodeBlock($event->getScrollData(), $template, 'orob2b.tax.product.section.taxes');
    }
}

<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adds tax code to the product view and edit pages.
 */
class ProductFormViewListener
{
    private RequestStack $requestStack;
    private DoctrineHelper $doctrineHelper;
    private FeatureChecker $featureChecker;

    public function __construct(
        RequestStack $requestStack,
        DoctrineHelper $doctrineHelper,
        FeatureChecker $featureChecker
    ) {
        $this->requestStack = $requestStack;
        $this->doctrineHelper = $doctrineHelper;
        $this->featureChecker = $featureChecker;
    }

    public function onView(BeforeListRenderEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        if (!$this->featureChecker->isResourceEnabled(ProductTaxCode::class, 'entities')) {
            return;
        }

        /** @var Product|null $product */
        $product = $this->doctrineHelper->getEntityReference(Product::class, (int)$request->get('id'));
        if (null === $product) {
            return;
        }

        $template = $event->getEnvironment()->render(
            '@OroTax/Product/tax_code_view.html.twig',
            ['entity' => $product->getTaxCode()]
        );
        $event->getScrollData()->addSubBlockData('general', 1, $template);
    }

    public function onEdit(BeforeListRenderEvent $event): void
    {
        if (!$this->featureChecker->isResourceEnabled(ProductTaxCode::class, 'entities')) {
            return;
        }

        $template = $event->getEnvironment()->render(
            '@OroTax/Product/tax_code_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $event->getScrollData()->addSubBlockData('general', 1, $template);
    }
}

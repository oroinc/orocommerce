<?php

namespace Oro\Bundle\ShippingBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Form\FieldAclHelper;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds shipping information to the product view and edit pages.
 */
class FormViewListener
{
    public const SHIPPING_BLOCK_NAME = 'shipping';
    public const SHIPPING_BLOCK_LABEL = 'oro.shipping.product.section.shipping_options';
    public const SHIPPING_BLOCK_PRIORITY = 1800;

    public function __construct(
        private TranslatorInterface $translator,
        private DoctrineHelper $doctrineHelper,
        private RequestStack $requestStack,
        private FieldAclHelper $fieldAclHelper
    ) {
    }

    public function onProductView(BeforeListRenderEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $productId = (int)$request->get('id');
        if (!$productId) {
            return;
        }

        /** @var Product $product */
        $product = $this->doctrineHelper->getEntityReference(Product::class, $productId);
        if (!$product) {
            return;
        }

        $shippingOptions = $this->doctrineHelper
            ->getEntityRepositoryForClass(ProductShippingOptions::class)
            ->findBy(['product' => $productId]);

        if (0 === count($shippingOptions)) {
            return;
        }

        if (!$this->fieldAclHelper->isFieldViewGranted($product, 'unitPrecisions')) {
            return;
        }

        $template = $event->getEnvironment()->render(
            '@OroShipping/Product/shipping_options_view.html.twig',
            [
                'entity' => $product,
                'shippingOptions' => $shippingOptions
            ]
        );

        $this->addBlock($event->getScrollData(), $template, self::SHIPPING_BLOCK_LABEL, self::SHIPPING_BLOCK_PRIORITY);
    }

    public function onProductEdit(BeforeListRenderEvent $event): void
    {
        if (!$this->fieldAclHelper->isFieldAvailable($event->getEntity(), 'unitPrecisions')) {
            return;
        }

        $template = $event->getEnvironment()->render(
            '@OroShipping/Product/shipping_options_update.html.twig',
            ['form' => $event->getFormView()]
        );

        $this->addBlock($event->getScrollData(), $template, self::SHIPPING_BLOCK_LABEL, self::SHIPPING_BLOCK_PRIORITY);
    }

    protected function addBlock(ScrollData $scrollData, string $html, string $label, int $priority): void
    {
        $blockLabel = $this->translator->trans($label);
        $scrollData->addNamedBlock(self::SHIPPING_BLOCK_NAME, $blockLabel, $priority);
        $subBlockId = $scrollData->addSubBlock(self::SHIPPING_BLOCK_NAME);
        $scrollData->addSubBlockData(self::SHIPPING_BLOCK_NAME, $subBlockId, $html);
    }
}

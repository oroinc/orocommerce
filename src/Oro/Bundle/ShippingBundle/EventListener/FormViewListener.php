<?php

namespace Oro\Bundle\ShippingBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds shipping information to the product view and edit pages.
 */
class FormViewListener
{
    public const SHIPPING_BLOCK_NAME     = 'shipping';
    public const SHIPPING_BLOCK_LABEL    = 'oro.shipping.product.section.shipping_options';
    public const SHIPPING_BLOCK_PRIORITY = 1800;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var RequestStack */
    protected $requestStack;

    public function __construct(
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        RequestStack $requestStack
    ) {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->requestStack = $requestStack;
    }

    public function onProductView(BeforeListRenderEvent $event)
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
        $product = $this->doctrineHelper->getEntityReference('OroProductBundle:Product', $productId);
        if (!$product) {
            return;
        }

        $shippingOptions = $this->doctrineHelper
            ->getEntityRepositoryForClass('OroShippingBundle:ProductShippingOptions')
            ->findBy(['product' => $productId]);

        if (0 === count($shippingOptions)) {
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

    public function onProductEdit(BeforeListRenderEvent $event)
    {
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

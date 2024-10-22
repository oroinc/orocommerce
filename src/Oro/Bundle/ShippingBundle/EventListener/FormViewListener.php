<?php

namespace Oro\Bundle\ShippingBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Form\FieldAclHelper;
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

    private ?FieldAclHelper $fieldAclHelper = null;

    public function __construct(
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        RequestStack $requestStack
    ) {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->requestStack = $requestStack;
    }

    public function setFieldAclHelper(FieldAclHelper $fieldAclHelper): void
    {
        $this->fieldAclHelper = $fieldAclHelper;
    }

    public function onProductView(BeforeListRenderEvent $event)
    {
        /** @var Product $product */
        $product = $event->getEntity();

        $shippingOptions = $this->doctrineHelper
            ->getEntityRepositoryForClass('OroShippingBundle:ProductShippingOptions')
            ->findBy(['product' => $product]);

        if (!$this->fieldAclHelper->isFieldViewGranted($product, 'unitPrecisions')) {
            return;
        }

        $template = $event->getEnvironment()->render(
            '@OroShipping/Product/shipping_options_view.html.twig',
            [
                'entity' => $product,
                'shippingOptions' => $shippingOptions,
                'kitShippingCalculationMethodValue' => $product->isKit() ? $this->translator->trans(sprintf(
                    'oro.product.kit_shipping_calculation_method.choices.%s',
                    $product->getKitShippingCalculationMethod()
                )) : null
            ]
        );

        $this->addBlock($event->getScrollData(), $template, self::SHIPPING_BLOCK_LABEL, self::SHIPPING_BLOCK_PRIORITY);
    }

    public function onProductEdit(BeforeListRenderEvent $event)
    {
        $product = $event->getEntity();
        $isKit = $product?->isKit();
        $isShippingOptionsFieldAvailable = $this
            ->fieldAclHelper
            ->isFieldAvailable($product, 'unitPrecisions');

        if (!$isShippingOptionsFieldAvailable && !$isKit) {
            return;
        }

        $template = $event->getEnvironment()->render(
            '@OroShipping/Product/shipping_options_update.html.twig',
            [
                'form' => $event->getFormView(),
                'isKit' => $isKit,
                'isShippingOptionsFieldAvailable' => $isShippingOptionsFieldAvailable
            ]
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

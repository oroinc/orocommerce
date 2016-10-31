<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

class ProductQuantityToOrderFormViewListener
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param RequestStack $requestStack
     * @param DoctrineHelper $doctrineHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(
        RequestStack $requestStack,
        DoctrineHelper $doctrineHelper,
        TranslatorInterface $translator
    ) {
        $this->requestStack = $requestStack;
        $this->doctrineHelper = $doctrineHelper;
        $this->translator = $translator;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onProductView(BeforeListRenderEvent $event)
    {
        $product = $this->getProductFromRequest();
        if (!$product) {
            return;
        }

        $template = $event->getEnvironment()->render(
            'OroInventoryBundle:Product:viewQuantityToOrder.html.twig',
            ['entity' => $product]
        );

        $this->addToInventoryBlock($event->getScrollData(), $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onProductEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroInventoryBundle:Product:editQuantityToOrder.html.twig',
            ['form' => $event->getFormView()]
        );

        $this->addToInventoryBlock($event->getScrollData(), $template);
    }

    /**
     * @param ScrollData $scrollData
     * @param string $template
     */
    protected function addToInventoryBlock(ScrollData $scrollData, $template)
    {
        $inventoryBlockLabel = $this->translator->trans('oro.product.sections.inventory');

        $data = $scrollData->getData()[ScrollData::DATA_BLOCKS];
        foreach ($data as $blockId => $blockInfo) {
            if ($blockInfo['title'] == $inventoryBlockLabel) {
                $scrollData->addSubBlockData($blockId, 0, $template);

                return;
            }
        }
    }

    /**
     * @return null|Product
     */
    protected function getProductFromRequest()
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return null;
        }

        $productId = (int)$request->get('id');
        if (!$productId) {
            return null;
        }

        return $this->doctrineHelper->getEntityReference(Product::class, $productId);
    }
}

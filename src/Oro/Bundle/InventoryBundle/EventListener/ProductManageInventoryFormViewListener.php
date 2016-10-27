<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class ProductManageInventoryFormViewListener
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
     * @param RequestStack $requestStack
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(RequestStack $requestStack, DoctrineHelper $doctrineHelper)
    {
        $this->requestStack = $requestStack;
        $this->doctrineHelper = $doctrineHelper;
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
            'OroInventoryBundle:Product:manageInventory.html.twig',
            ['entity' => $product]
        );

        $event->getScrollData()->addSubBlockData(0, 0, $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onProductEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroInventoryBundle:Product:manageInventoryFormWidget.html.twig',
            ['form' => $event->getFormView()]
        );
        $event->getScrollData()->addSubBlockData(0, 0, $template);
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

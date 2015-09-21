<?php

namespace OroB2B\Bundle\ShoppingListBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

class FormViewListener
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(TranslatorInterface $translator, DoctrineHelper $doctrineHelper)
    {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param Request $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onFrontendProductView(BeforeListRenderEvent $event)
    {
        if (!$this->request) {
            return;
        }

        $productId = (int)$this->request->get('id');
        if (!$productId) {
            return;
        }

        if (!$this->doctrineHelper->getEntityReference('OroB2BProductBundle:Product', $productId)) {
            return;
        }

        $template = $event->getEnvironment()->render(
            'OroB2BShoppingListBundle:Product/Frontend:view.html.twig',
            ['productId' => $productId]
        );

        $this->addShoppingListBlock($event->getScrollData(), $template);
    }

    /**
     * @param ScrollData $scrollData
     * @param string $html
     */
    protected function addShoppingListBlock(ScrollData $scrollData, $html)
    {
        $blockLabel = $this->translator->trans('orob2b.shoppinglist.product.add_to_shopping_list.label');
        $blockId = $scrollData->addBlock($blockLabel);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $html);
    }
}

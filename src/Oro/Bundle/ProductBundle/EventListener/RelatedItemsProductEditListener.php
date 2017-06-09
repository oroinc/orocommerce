<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class RelatedItemsProductEditListener
{
    const RELATED_ITEMS_ID = 'relatedItems';

    /** @var int */
    const BLOCK_PRIORITY = 10;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onProductEdit(BeforeListRenderEvent $event)
    {
        $twigEnv = $event->getEnvironment();
        $upsellProductsTemplate = $twigEnv->render(
            '@OroProduct/Product/RelatedItems/upsellItems.html.twig',
            [
                'form' => $event->getFormView(),
                'entity' => $event->getEntity()
            ]
        );
        $this->addEditPageBlock($event->getScrollData(), $upsellProductsTemplate);
    }

    /**
     * @param ScrollData $scrollData
     * @param string $upsellProductsForm
     */
    private function addEditPageBlock(ScrollData $scrollData, $upsellProductsForm)
    {
        $scrollData->addNamedBlock(
            self::RELATED_ITEMS_ID,
            $this->translator->trans('oro.product.sections.relatedItems'),
            self::BLOCK_PRIORITY
        );
        $subBlock = $scrollData->addSubBlock(self::RELATED_ITEMS_ID);
        $scrollData->addSubBlockData(self::RELATED_ITEMS_ID, $subBlock, $upsellProductsForm, 'relatedItems');
    }
}

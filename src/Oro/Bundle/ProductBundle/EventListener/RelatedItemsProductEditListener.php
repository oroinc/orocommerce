<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ProductBundle\RelatedItem\AbstractRelatedItemConfigProvider;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class RelatedItemsProductEditListener
{
    const RELATED_ITEMS_ID = 'relatedItems';

    /** @var int */
    const BLOCK_PRIORITY = 10;

    /** @var TranslatorInterface */
    private $translator;

    /** @var AbstractRelatedItemConfigProvider */
    private $configProvider;

    /**
     * @param TranslatorInterface               $translator
     * @param AbstractRelatedItemConfigProvider $configProvider
     */
    public function __construct(TranslatorInterface $translator, AbstractRelatedItemConfigProvider $configProvider)
    {
        $this->translator = $translator;
        $this->configProvider = $configProvider;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onProductEdit(BeforeListRenderEvent $event)
    {
        if (!$this->configProvider->isEnabled()) {
            return;
        }

        $twigEnv = $event->getEnvironment();
        $relatedProductsTemplate = $twigEnv->render(
            '@OroProduct/Product/RelatedItems/relatedItems.html.twig',
            [
                'form' => $event->getFormView(),
                'entity' => $event->getEntity()
            ]
        );
        $this->addEditPageBlock($event->getScrollData(), $relatedProductsTemplate);
    }

    /**
     * @param ScrollData $scrollData
     * @param string $relatedProductsForm
     */
    private function addEditPageBlock(ScrollData $scrollData, $relatedProductsForm)
    {
        $scrollData->addNamedBlock(
            self::RELATED_ITEMS_ID,
            $this->translator->trans('oro.product.sections.relatedItems'),
            self::BLOCK_PRIORITY
        );
        $subBlock = $scrollData->addSubBlock(self::RELATED_ITEMS_ID);
        $scrollData->addSubBlockData(self::RELATED_ITEMS_ID, $subBlock, $relatedProductsForm, 'relatedItems');
    }
}

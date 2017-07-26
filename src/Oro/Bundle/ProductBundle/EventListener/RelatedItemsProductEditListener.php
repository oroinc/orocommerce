<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
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
    private $relatedProductsConfigProvider;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var AbstractRelatedItemConfigProvider */
    private $upsellProductsConfigProvider;

    /**
     * @param TranslatorInterface               $translator
     * @param AbstractRelatedItemConfigProvider $relatedProductsConfigProvider
     * @param AbstractRelatedItemConfigProvider $upsellProductsConfigProvider
     * @param AuthorizationCheckerInterface     $authorizationChecker
     */
    public function __construct(
        TranslatorInterface $translator,
        AbstractRelatedItemConfigProvider $relatedProductsConfigProvider,
        AbstractRelatedItemConfigProvider $upsellProductsConfigProvider,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->translator = $translator;
        $this->relatedProductsConfigProvider = $relatedProductsConfigProvider;
        $this->authorizationChecker = $authorizationChecker;
        $this->upsellProductsConfigProvider = $upsellProductsConfigProvider;
    }


    /**
     * @param BeforeListRenderEvent $event
     */
    public function onProductEdit(BeforeListRenderEvent $event)
    {
        $twigEnv = $event->getEnvironment();
        $tabs = [];
        $grids = [];

        if ($this->relatedProductsConfigProvider->isEnabled()
            && $this->authorizationChecker->isGranted('oro_related_products_edit')
        ) {
            $tabs[] = [
                'id' => 'related-products-block',
                'label' => $this->translator->trans('oro.product.tabs.relatedProducts')
            ];
            $grids[] = $this->getRelatedProductsEditBlock($event, $twigEnv);
        }

        if ($this->upsellProductsConfigProvider->isEnabled()
            && $this->authorizationChecker->isGranted('oro_upsell_products_edit')
        ) {
            $tabs[] = [
                'id' => 'upsell-products-block',
                'label' => $this->translator->trans('oro.product.tabs.upsellProducts')
            ];
            $grids[] = $this->getUpsellProductsEdidBlock($event, $twigEnv);
        }

        if (count($tabs) > 1) {
            $grids = array_merge([$this->renderTabs($twigEnv, $tabs)], $grids);
        }

        $this->addEditPageBlock($event->getScrollData(), $grids);
    }

    /**
     * @param FormProcessEvent $event
     */
    public function onFormDataSet(FormProcessEvent $event)
    {
        if ($this->authorizationChecker->isGranted('oro_related_products_edit')) {
            $event->getForm()->add(
                'appendRelated',
                'oro_entity_identifier',
                [
                    'class' => Product::class,
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            );
            $event->getForm()->add(
                'removeRelated',
                'oro_entity_identifier',
                [
                    'class' => Product::class,
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            );
        } else {
            $event->getForm()->remove('appendRelated');
            $event->getForm()->remove('removeRelated');
        }

        if ($this->authorizationChecker->isGranted('oro_upsell_products_edit')) {
            $event->getForm()->add(
                'appendUpsell',
                'oro_entity_identifier',
                [
                    'class' => Product::class,
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            );
            $event->getForm()->add(
                'removeUpsell',
                'oro_entity_identifier',
                [
                    'class' => Product::class,
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            );
        } else {
            $event->getForm()->remove('appendUpsell');
            $event->getForm()->remove('removeUpsell');
        }
    }

    /**
     * @param ScrollData $scrollData
     * @param string[] $htmlBlocks
     */
    private function addEditPageBlock(ScrollData $scrollData, array $htmlBlocks)
    {
        $scrollData->addNamedBlock(
            self::RELATED_ITEMS_ID,
            $this->translator->trans('oro.product.sections.relatedItems'),
            self::BLOCK_PRIORITY
        );

        $subBlock = $scrollData->addSubBlock(self::RELATED_ITEMS_ID);
        $scrollData->addSubBlockData(
            self::RELATED_ITEMS_ID,
            $subBlock,
            implode('', $htmlBlocks),
            'relatedItems'
        );
    }

    /**
     * @param BeforeListRenderEvent $event
     * @param \Twig_Environment $twigEnv
     * @return string
     */
    private function getRelatedProductsEditBlock(BeforeListRenderEvent $event, \Twig_Environment $twigEnv)
    {
        return $twigEnv->render(
            '@OroProduct/Product/RelatedItems/relatedProducts.html.twig',
            [
                'form' => $event->getFormView(),
                'entity' => $event->getEntity(),
                'relatedProductsLimit' => $this->relatedProductsConfigProvider->getLimit()
            ]
        );
    }

    /**
     * @param BeforeListRenderEvent $event
     * @param \Twig_Environment $twigEnv
     * @return string
     */
    private function getUpsellProductsEdidBlock(BeforeListRenderEvent $event, \Twig_Environment $twigEnv)
    {
        return $twigEnv->render(
            '@OroProduct/Product/RelatedItems/upsellProducts.html.twig',
            [
                'form' => $event->getFormView(),
                'entity' => $event->getEntity(),
                'upsellProductsLimit' => $this->upsellProductsConfigProvider->getLimit(),
            ]
        );
    }

    /**
     * @param \Twig_Environment $twigEnv
     * @param array $tabs
     * @return string
     */
    private function renderTabs(\Twig_Environment $twigEnv, array $tabs)
    {
        return $twigEnv->render(
            '@OroProduct/Product/RelatedItems/tabs.html.twig',
            [
                'relatedItemsTabsItems' => $tabs
            ]
        );
    }
}

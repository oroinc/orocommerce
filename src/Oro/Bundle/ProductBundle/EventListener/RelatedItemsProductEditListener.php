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

        if ($this->relatedProductsConfigProvider->isEnabled()
            && $this->authorizationChecker->isGranted('oro_related_products_edit')
        ) {
            $this->addRelatedProductsEditBlock($event, $twigEnv);
        }

        if ($this->upsellProductsConfigProvider->isEnabled()
            && $this->authorizationChecker->isGranted('oro_upsell_products_edit')
        ) {
            $this->addUpsellProductsEdidBlock($event, $twigEnv);
        }
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

    /**
     * @param BeforeListRenderEvent $event
     * @param \Twig_Environment $twigEnv
     */
    private function addRelatedProductsEditBlock(BeforeListRenderEvent $event, \Twig_Environment $twigEnv)
    {
        $relatedProductsTemplate = $twigEnv->render(
            '@OroProduct/Product/RelatedItems/relatedItems.html.twig',
            [
                'form' => $event->getFormView(),
                'entity' => $event->getEntity(),
                'relatedProductsLimit' => $this->relatedProductsConfigProvider->getLimit()
            ]
        );
        $this->addEditPageBlock($event->getScrollData(), $relatedProductsTemplate);
    }

    /**
     * @param BeforeListRenderEvent $event
     * @param \Twig_Environment $twigEnv
     */
    private function addUpsellProductsEdidBlock(BeforeListRenderEvent $event, \Twig_Environment $twigEnv)
    {
        $upsellProductsTemplate = $twigEnv->render(
            '@OroProduct/Product/RelatedItems/upsellItems.html.twig',
            [
                'form' => $event->getFormView(),
                'entity' => $event->getEntity()
            ]
        );
        $this->addEditPageBlock($event->getScrollData(), $upsellProductsTemplate);
    }
}

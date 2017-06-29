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
    private $configProvider;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /**
     * @param TranslatorInterface               $translator
     * @param AbstractRelatedItemConfigProvider $configProvider
     * @param AuthorizationCheckerInterface     $authorizationChecker
     */
    public function __construct(
        TranslatorInterface $translator,
        AbstractRelatedItemConfigProvider $configProvider,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->translator = $translator;
        $this->configProvider = $configProvider;
        $this->authorizationChecker = $authorizationChecker;
    }


    /**
     * @param BeforeListRenderEvent $event
     */
    public function onProductEdit(BeforeListRenderEvent $event)
    {
        if (!$this->configProvider->isEnabled()
            || !$this->authorizationChecker->isGranted('oro_related_products_edit')
        ) {
            return;
        }

        $twigEnv = $event->getEnvironment();
        
        $relatedProductsTemplate = $twigEnv->render(
            '@OroProduct/Product/RelatedItems/relatedItems.html.twig',
            [
                'form' => $event->getFormView(),
                'entity' => $event->getEntity(),
                'relatedProductsLimit' => $this->configProvider->getLimit()
            ]
        );
        
        $upsellProductsTemplate = $twigEnv->render(
            '@OroProduct/Product/RelatedItems/upsellItems.html.twig',
            [
                'form' => $event->getFormView(),
                'entity' => $event->getEntity()
            ]
        );
        $this->addEditPageBlock($event->getScrollData(), $relatedProductsTemplate);
        $this->addEditPageBlock($event->getScrollData(), $upsellProductsTemplate);
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

        //TODO - add permission checking in BB_10067
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

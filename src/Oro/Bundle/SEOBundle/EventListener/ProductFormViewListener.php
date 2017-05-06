<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class ProductFormViewListener extends BaseFormViewListener
{
    /**
     * Insert SEO information
     *
     * @param BeforeListRenderEvent $event
     */
    public function onProductView(BeforeListRenderEvent $event)
    {
        $product = $this->extractEntityFromCurrentRequest(Product::class);
        if (!$product instanceof Product) {
            return;
        }

        $twigEnv = $event->getEnvironment();
        $descriptionTemplate = $twigEnv->render('OroSEOBundle:SEO:description_view.html.twig', [
            'entity' => $product,
            'labelPrefix' => $this->getMetaFieldLabelPrefix()
        ]);
        $keywordsTemplate = $twigEnv->render('OroSEOBundle:SEO:keywords_view.html.twig', [
            'entity' => $product,
            'labelPrefix' => $this->getMetaFieldLabelPrefix()
        ]);
        $slugsTemplate = $twigEnv->render('OroRedirectBundle::entitySlugs.html.twig', [
            'entitySlugs' => $product->getSlugs()
        ]);
        $scrollData = $event->getScrollData();
        $blockLabel = $this->translator->trans('oro.seo.label');
        $scrollData->addNamedBlock(self::SEO_BLOCK_ID, $blockLabel, 800);
        $subBlock = $scrollData->addSubBlock(self::SEO_BLOCK_ID);
        $scrollData->addSubBlockData(self::SEO_BLOCK_ID, $subBlock, $slugsTemplate, 'generatedSlugs');
        $scrollData->addSubBlockData(self::SEO_BLOCK_ID, $subBlock, $descriptionTemplate, 'metaDescriptions');
        $scrollData->addSubBlockData(self::SEO_BLOCK_ID, $subBlock, $keywordsTemplate, 'metaKeywords');
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onProductEdit(BeforeListRenderEvent $event)
    {
        $this->addEditPageBlock($event);
    }

    /**
     * @return string
     */
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.product';
    }
}

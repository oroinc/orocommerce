<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Component\Exception\UnexpectedTypeException;

/**
 * Adds SEO information to the product view and edit pages.
 */
class ProductFormViewListener extends BaseFormViewListener
{
    /**
     * Insert SEO information
     */
    public function onProductView(BeforeListRenderEvent $event)
    {
        $product = $event->getEntity();

        if (!$product instanceof Product) {
            throw new UnexpectedTypeException($product, Product::class);
        }

        $this->addViewPageBlock($event);
    }

    /**
     * {@inheritDoc}
     */
    protected function addViewPageBlock(BeforeListRenderEvent $event, $priority = 10)
    {
        $product = $event->getEntity();

        $twigEnv = $event->getEnvironment();
        $titleTemplate = $twigEnv->render('@OroSEO/SEO/title_view.html.twig', [
            'entity' => $product,
            'labelPrefix' => $this->getMetaFieldLabelPrefix()
        ]);
        $descriptionTemplate = $twigEnv->render('@OroSEO/SEO/description_view.html.twig', [
            'entity' => $product,
            'labelPrefix' => $this->getMetaFieldLabelPrefix()
        ]);
        $keywordsTemplate = $twigEnv->render('@OroSEO/SEO/keywords_view.html.twig', [
            'entity' => $product,
            'labelPrefix' => $this->getMetaFieldLabelPrefix()
        ]);
        $slugsTemplate = $twigEnv->render('@OroRedirect/entitySlugs.html.twig', [
            'entitySlugs' => $product->getSlugs()
        ]);
        $scrollData = $event->getScrollData();
        $blockLabel = $this->translator->trans('oro.seo.label');
        $scrollData->addNamedBlock(self::SEO_BLOCK_ID, $blockLabel, 1700);
        $subBlock = $scrollData->addSubBlock(self::SEO_BLOCK_ID);
        $scrollData->addSubBlockData(self::SEO_BLOCK_ID, $subBlock, $slugsTemplate, 'generatedSlugs');
        $scrollData->addSubBlockData(self::SEO_BLOCK_ID, $subBlock, $titleTemplate, 'metaTitles');
        $scrollData->addSubBlockData(self::SEO_BLOCK_ID, $subBlock, $descriptionTemplate, 'metaDescriptions');
        $scrollData->addSubBlockData(self::SEO_BLOCK_ID, $subBlock, $keywordsTemplate, 'metaKeywords');
    }

    public function onProductEdit(BeforeListRenderEvent $event)
    {
        $this->addEditPageBlock($event, 1700);
    }

    /**
     * @return string
     */
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.product';
    }
}

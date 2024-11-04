<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Form\FieldAclHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\Exception\UnexpectedTypeException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Adds SEO information to the product view and edit pages.
 */
class ProductFormViewListener extends BaseFormViewListener
{
    public function __construct(TranslatorInterface $translator, private FieldAclHelper $fieldAclHelper)
    {
        parent::__construct($translator);
    }

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

    #[\Override]
    protected function addViewPageBlock(BeforeListRenderEvent $event, $priority = 10)
    {
        $entity = $event->getEntity();
        $env = $event->getEnvironment();

        $titleTemplate = $this->getViewPageSubBlock(
            $env,
            $entity,
            'metaTitles',
            '@OroSEO/SEO/title_view.html.twig',
            ['entity' => $entity, 'labelPrefix' => $this->getMetaFieldLabelPrefix()]
        );

        $descriptionTemplate = $this->getViewPageSubBlock(
            $env,
            $entity,
            'metaDescriptions',
            '@OroSEO/SEO/description_view.html.twig',
            ['entity' => $entity, 'labelPrefix' => $this->getMetaFieldLabelPrefix()]
        );

        $keywordsTemplate = $this->getViewPageSubBlock(
            $env,
            $entity,
            'metaKeywords',
            '@OroSEO/SEO/keywords_view.html.twig',
            ['entity' => $entity, 'labelPrefix' => $this->getMetaFieldLabelPrefix()]
        );
        $slugsTemplate = $this->getViewPageSubBlock(
            $env,
            $entity,
            'slugs',
            '@OroRedirect/entitySlugs.html.twig',
            ['entitySlugs' => $entity->getSlugs()]
        );

        $scrollData = $event->getScrollData();
        if ($titleTemplate || $descriptionTemplate || $keywordsTemplate || $slugsTemplate) {
            $blockLabel = $this->translator->trans('oro.seo.label');
            $scrollData->addNamedBlock(self::SEO_BLOCK_ID, $blockLabel, 1700);
            $subBlock = $scrollData->addSubBlock(self::SEO_BLOCK_ID);
            $this->addSubBlockData($scrollData, $subBlock, 'generatedSlugs', $slugsTemplate);
            $this->addSubBlockData($scrollData, $subBlock, 'metaTitles', $titleTemplate);
            $this->addSubBlockData($scrollData, $subBlock, 'metaDescriptions', $descriptionTemplate);
            $this->addSubBlockData($scrollData, $subBlock, 'metaKeywords', $keywordsTemplate);
        }
    }

    public function onProductEdit(BeforeListRenderEvent $event)
    {
        $scrollData = $event->getScrollData();
        $env = $event->getEnvironment();
        $formView = $event->getFormView();
        $entity = $event->getEntity();

        $titleTemplate = $this->getViewPageSubBlock(
            $env,
            $entity,
            'metaTitles',
            '@OroSEO/SEO/title_update.html.twig',
            ['form' => $formView],
            true
        );
        $descTemplate = $this->getViewPageSubBlock(
            $env,
            $entity,
            'metaDescriptions',
            '@OroSEO/SEO/description_update.html.twig',
            ['form' => $formView],
            true
        );
        $keywordsTemplate = $this->getViewPageSubBlock(
            $env,
            $entity,
            'metaKeywords',
            '@OroSEO/SEO/keywords_update.html.twig',
            ['form' => $formView],
            true
        );

        if ($titleTemplate || $descTemplate || $keywordsTemplate) {
            $blockLabel = $this->translator->trans('oro.seo.label');
            $scrollData->addNamedBlock(self::SEO_BLOCK_ID, $blockLabel, 1700);
            $leftSubBlock = $scrollData->addSubBlock(self::SEO_BLOCK_ID);
            $rightSubBlock = $scrollData->addSubBlock(self::SEO_BLOCK_ID);

            $this->addSubBlockData($scrollData, $leftSubBlock, 'metaTitles', $titleTemplate);
            $this->addSubBlockData($scrollData, $leftSubBlock, 'metaDescriptions', $descTemplate);
            $this->addSubBlockData($scrollData, $rightSubBlock, 'metaKeywords', $keywordsTemplate);
        }
    }

    private function getViewPageSubBlock(
        Environment $env,
        object $entity,
        string $field,
        string $template,
        array $templateData,
        bool $editable = false
    ): ?string {
        $isAccessible = $editable
            ? $this->fieldAclHelper->isFieldAvailable($entity, $field)
            : $this->fieldAclHelper->isFieldViewGranted($entity, $field);

        if ($isAccessible) {
            return $env->render($template, $templateData);
        }

        return null;
    }

    private function addSubBlockData(ScrollData $scrollData, $subBlock, $fieldName, ?string $html = null): void
    {
        if (!$html) {
            return;
        }

        $scrollData->addSubBlockData(self::SEO_BLOCK_ID, $subBlock, $html, $fieldName);
    }

    /**
     * @return string
     */
    #[\Override]
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.product';
    }
}

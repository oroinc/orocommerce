<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

abstract class BaseFormViewListener
{
    const SEO_BLOCK_ID = 'seo';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var int
     */
    protected $blockPriority = 10;

    /**
     * @param RequestStack $requestStack
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        RequestStack $requestStack,
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper
    ) {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param int $blockPriority
     * @return BaseFormViewListener
     */
    public function setBlockPriority($blockPriority)
    {
        $this->blockPriority = $blockPriority;

        return $this;
    }

    /**
     * @param BeforeListRenderEvent $event
     * @param string $entityClass
     */
    protected function addViewPageBlock(BeforeListRenderEvent $event, $entityClass)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $objectId = (int)$request->get('id');
        if (!$objectId) {
            return;
        }

        $object = $this->doctrineHelper->getEntityReference($entityClass, $objectId);
        if (!$object) {
            return;
        }

        $twigEnv = $event->getEnvironment();
        $descriptionTemplate = $twigEnv->render('OroSEOBundle:SEO:description_view.html.twig', [
            'entity' => $object,
            'labelPrefix' => $this->getMetaFieldLabelPrefix()
        ]);
        $keywordsTemplate = $twigEnv->render('OroSEOBundle:SEO:keywords_view.html.twig', [
            'entity' => $object,
            'labelPrefix' => $this->getMetaFieldLabelPrefix()
        ]);

        $this->addSEOBlock($event->getScrollData(), $descriptionTemplate, $keywordsTemplate);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    protected function addEditPageBlock(BeforeListRenderEvent $event)
    {
        $twigEnv = $event->getEnvironment();
        $formView = $event->getFormView();
        $descriptionTemplate = $twigEnv->render(
            'OroSEOBundle:SEO:description_update.html.twig',
            ['form' => $formView]
        );
        $keywordsTemplate = $twigEnv->render(
            'OroSEOBundle:SEO:keywords_update.html.twig',
            ['form' => $formView]
        );

        $this->addSEOBlock($event->getScrollData(), $descriptionTemplate, $keywordsTemplate);
    }

    /**
     * @param ScrollData $scrollData
     * @param string $descriptionTemplate
     * @param string $keywordsTemplate
     */
    protected function addSEOBlock(ScrollData $scrollData, $descriptionTemplate, $keywordsTemplate)
    {
        $blockLabel = $this->translator->trans('oro.seo.label');
        $scrollData->addNamedBlock(self::SEO_BLOCK_ID, $blockLabel, $this->blockPriority);
        $leftSubBlock = $scrollData->addSubBlock(self::SEO_BLOCK_ID);
        $rightSubBlock = $scrollData->addSubBlock(self::SEO_BLOCK_ID);
        $scrollData->addSubBlockData(self::SEO_BLOCK_ID, $leftSubBlock, $descriptionTemplate, 'metaDescriptions');
        $scrollData->addSubBlockData(self::SEO_BLOCK_ID, $rightSubBlock, $keywordsTemplate, 'metaKeywords');
    }

    /**
     * @return string
     */
    abstract public function getMetaFieldLabelPrefix();
}

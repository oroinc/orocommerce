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
     * @param BeforeListRenderEvent $event
     * @param string $entityClass
     * @param int $priority
     */
    protected function addViewPageBlock(BeforeListRenderEvent $event, $entityClass, $priority = 10)
    {
        $object = $this->extractEntityFromCurrentRequest($entityClass);
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

        $this->addSEOBlock($event->getScrollData(), $descriptionTemplate, $keywordsTemplate, $priority);
    }

    /**
     * @param BeforeListRenderEvent $event
     * @param int $priority
     */
    protected function addEditPageBlock(BeforeListRenderEvent $event, $priority = 10)
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

        $this->addSEOBlock($event->getScrollData(), $descriptionTemplate, $keywordsTemplate, $priority);
    }

    /**
     * @param ScrollData $scrollData
     * @param string $descriptionTemplate
     * @param string $keywordsTemplate
     * @param int $priority
     */
    protected function addSEOBlock(ScrollData $scrollData, $descriptionTemplate, $keywordsTemplate, $priority = 10)
    {
        $blockLabel = $this->translator->trans('oro.seo.label');
        $scrollData->addNamedBlock(self::SEO_BLOCK_ID, $blockLabel, $priority);
        $leftSubBlock = $scrollData->addSubBlock(self::SEO_BLOCK_ID);
        $rightSubBlock = $scrollData->addSubBlock(self::SEO_BLOCK_ID);
        $scrollData->addSubBlockData(self::SEO_BLOCK_ID, $leftSubBlock, $descriptionTemplate, 'metaDescriptions');
        $scrollData->addSubBlockData(self::SEO_BLOCK_ID, $rightSubBlock, $keywordsTemplate, 'metaKeywords');
    }

    /**
     * @param string $entityClass
     * @return null|object
     */
    protected function extractEntityFromCurrentRequest($entityClass)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }

        $objectId = (int)$request->get('id');
        if (!$objectId) {
            return null;
        }

        return $this->doctrineHelper->getEntity($entityClass, $objectId);
    }

    /**
     * @return string
     */
    abstract public function getMetaFieldLabelPrefix();
}

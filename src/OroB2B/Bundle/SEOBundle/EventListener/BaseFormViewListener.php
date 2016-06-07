<?php

namespace OroB2B\Bundle\SEOBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

abstract class BaseFormViewListener
{
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
     */
    protected function addViewPageBlock(BeforeListRenderEvent $event, $entitiyClass)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $objectId = (int)$request->get('id');
        if (!$objectId) {
            return;
        }

        $object = $this->doctrineHelper->getEntityReference($entitiyClass, $objectId);
        if (!$object) {
            return;
        }

        $template = $event->getEnvironment()->render('OroB2BSEOBundle:SEO:view.html.twig', ['entity' => $object]);

        $this->addSEOBlock($event->getScrollData(), $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    protected function addEditPageBlock(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroB2BSEOBundle:SEO:update.html.twig',
            ['form' => $event->getFormView()]
        );

        $this->addSEOBlock($event->getScrollData(), $template);
    }

    /**
     * @param ScrollData $scrollData
     * @param string $html
     */
    protected function addSEOBlock(ScrollData $scrollData, $html)
    {
        $blockLabel = $this->translator->trans('orob2b.seo.label');
        $blockId = $scrollData->addBlock($blockLabel, 10);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $html);
    }

    /**
     * @return string
     */
    public abstract function getExtendedEntitySuffix();
}

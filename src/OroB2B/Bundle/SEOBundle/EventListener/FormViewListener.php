<?php

namespace OroB2B\Bundle\SEOBundle\EventListener;

use OroB2B\Bundle\CMSBundle\Entity\Page;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class FormViewListener
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
     * Insert SEO information 
     *
     * @param BeforeListRenderEvent $event
     */
    public function onProductView(BeforeListRenderEvent $event)
    {
        $this->addViewPageBlock($event, 'OroB2BProductBundle:Product', 'OroB2BSEOBundle:Product:seo_view.html.twig');
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onLandingPageView(BeforeListRenderEvent $event)
    {
        $this->addViewPageBlock($event, 'OroB2BCMSBundle:Page', 'OroB2BSEOBundle:Page:seo_view.html.twig');
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onLandingPageEdit(BeforeListRenderEvent $event)
    {
        $this->addEditPageBlock($event, 'OroB2BSEOBundle:Page:seo_update.html.twig');
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    protected function addViewPageBlock(BeforeListRenderEvent $event, $entitiyClass, $template)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $objectId = (int)$request->get('id');
        /** @var Page $object */
        $object = $this->doctrineHelper->getEntityReference($entitiyClass, $objectId);

        $template = $event->getEnvironment()->render($template, ['entity' => $object]);

        $this->addSEOBlock($event->getScrollData(), $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    protected function addEditPageBlock(BeforeListRenderEvent $event, $template)
    {
        $template = $event->getEnvironment()->render(
            $template,
            ['form' => $event->getFormView()]
        );

        $this->addSEOBlock($event->getScrollData(), $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onProductEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroB2BSEOBundle:Product:seo_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $this->addProductPricesBlock($event->getScrollData(), $template);
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
}

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
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $productId = (int)$request->get('id');
        /** @var Product $product */
        $product = $this->doctrineHelper->getEntityReference('OroB2BProductBundle:Product', $productId);

        $template = $event->getEnvironment()->render(
            'OroB2BSEOBundle:Product:seo_view.html.twig',
            ['entity' => $product]
        );
        $this->addSEOBlock($event->getScrollData(), $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onLandingPageView(BeforeListRenderEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $pageId = (int)$request->get('id');
        /** @var Page $page */
        $page = $this->doctrineHelper->getEntityReference('OroB2BCMSBundle:Page', $pageId);

        $template = $event->getEnvironment()->render(
            'OroB2BSEOBundle:Page:seo_view.html.twig',
            ['entity' => $page]
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
}

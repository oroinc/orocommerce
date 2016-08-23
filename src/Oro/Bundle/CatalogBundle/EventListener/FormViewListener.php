<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;

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
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     * @param RequestStack $requestStack
     */
    public function __construct(
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        RequestStack $requestStack
    ) {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->requestStack = $requestStack;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onProductView(BeforeListRenderEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return;
        }

        $productId = (int)$request->get('id');
        if (!$productId) {
            return;
        }

        /** @var Product $product */
        $product = $this->doctrineHelper->getEntityReference('OroProductBundle:Product', $productId);
        if (!$product) {
            return;
        }

        /** @var CategoryRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository('OroCatalogBundle:Category');
        $category = $repository->findOneByProduct($product);

        $template = $event->getEnvironment()->render(
            'OroCatalogBundle:Product:category_view.html.twig',
            ['entity' => $category]
        );
        $this->addCategoryBlock($event->getScrollData(), $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onProductEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroCatalogBundle:Product:category_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $this->addCategoryBlock($event->getScrollData(), $template);
    }

    /**
     * @param ScrollData $scrollData
     * @param string     $html
     */
    protected function addCategoryBlock(ScrollData $scrollData, $html)
    {
        $blockLabel = $this->translator->trans('oro.catalog.product.section.catalog');
        $blockId    = $scrollData->addBlock($blockLabel);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $html);
    }
}

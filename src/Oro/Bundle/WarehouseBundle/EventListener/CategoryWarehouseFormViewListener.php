<?php

namespace Oro\Bundle\WarehouseBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class CategoryWarehouseFormViewListener
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param RequestStack $requestStack
     * @param DoctrineHelper $doctrineHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(
        RequestStack $requestStack,
        DoctrineHelper $doctrineHelper,
        TranslatorInterface $translator
    ) {
        $this->requestStack = $requestStack;
        $this->doctrineHelper = $doctrineHelper;
        $this->translator = $translator;
    }

    public function onCategoryEdit(BeforeListRenderEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return;
        }

        $categoryId = (int)$request->get('id');
        if (!$categoryId) {
            return;
        }

        /** @var Product $category */
        $category = $this->doctrineHelper->getEntityReference('OroCatalogBundle:Category', $categoryId);
        if (!$category) {
            return;
        }

        $template = $event->getEnvironment()->render(
            'OroWarehouseBundle:Category:editManageInventory.html.twig',
            ['form' => $event->getFormView()]
        );

        $scrollData = $event->getScrollData();
        $data = $scrollData->getData();
        foreach ($data['dataBlocks'] as $blockId => $blockData) {
            if ($blockData['title'] == $this->translator->trans('oro.catalog.sections.default_options')) {
                break;
            }
        }

        $scrollData->addSubBlockData($blockId, 0, $template);
    }
}

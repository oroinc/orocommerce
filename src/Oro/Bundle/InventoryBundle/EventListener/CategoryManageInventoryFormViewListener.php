<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class CategoryManageInventoryFormViewListener
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

    /**
     * @param BeforeListRenderEvent $event
     */
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

        /** @var Category $category */
        $category = $this->doctrineHelper->getEntityReference(Category::class, $categoryId);
        if (!$category) {
            return;
        }

        $template = $event->getEnvironment()->render(
            'OroInventoryBundle:Category:editManageInventory.html.twig',
            ['form' => $event->getFormView()]
        );

        $scrollData = $event->getScrollData();
        $data = $scrollData->getData();
        $expectedLabel = $this->translator->trans('oro.catalog.sections.default_options');
        foreach ($data['dataBlocks'] as $blockId => $blockData) {
            if ($blockData['title'] == $expectedLabel) {
                $scrollData->addSubBlockData($blockId, 0, $template);

                return;
            }
        }
    }
}

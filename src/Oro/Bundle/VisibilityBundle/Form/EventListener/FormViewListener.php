<?php

namespace Oro\Bundle\VisibilityBundle\Form\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds visibility information to the category edit page.
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

    public function __construct(
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        RequestStack $requestStack
    ) {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->requestStack = $requestStack;
    }

    public function onCategoryEdit(BeforeListRenderEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $categoryId = $request->get('id');

        /** @var Category $category */
        $category = $this->doctrineHelper->getEntityReference('OroCatalogBundle:Category', $categoryId);
        if ($category) {
            $template = $event->getEnvironment()->render(
                '@OroVisibility/Category/customer_category_visibility_edit.html.twig',
                [
                    'entity' => $category,
                    'form' => $event->getFormView(),
                ]
            );
            $this->addCustomerCategoryVisibilityBlock($event->getScrollData(), $template);
        }
    }

    /**
     * @param ScrollData $scrollData
     * @param string $html
     */
    protected function addCustomerCategoryVisibilityBlock(ScrollData $scrollData, $html)
    {
        $blockLabel = $this->translator->trans('oro.visibility.categoryvisibility.visibility.label');
        $blockId = $scrollData->addBlock($blockLabel);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $html);
    }
}

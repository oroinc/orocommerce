<?php

namespace Oro\Bundle\CustomerBundle\Form\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\CatalogBundle\Entity\Category;

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
                'OroCustomerBundle:Category:account_category_visibility_edit.html.twig',
                [
                    'entity' => $category,
                    'form' => $event->getFormView(),
                ]
            );
            $this->addAccountCategoryVisibilityBlock($event->getScrollData(), $template);
        }
    }

    /**
     * @param ScrollData $scrollData
     * @param string $html
     */
    protected function addAccountCategoryVisibilityBlock(ScrollData $scrollData, $html)
    {
        $blockLabel = $this->translator->trans('oro.account.visibility.categoryvisibility.visibility.label');
        $blockId = $scrollData->addBlock($blockLabel);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $html);
    }
}

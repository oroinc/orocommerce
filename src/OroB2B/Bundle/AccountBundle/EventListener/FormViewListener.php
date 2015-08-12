<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

use OroB2B\Bundle\CatalogBundle\Entity\Category;

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
     * @var Request
     */
    protected $request;

    /**
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(TranslatorInterface $translator, DoctrineHelper $doctrineHelper)
    {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param Request $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onCategoryEdit(BeforeListRenderEvent $event)
    {
        if (!$this->request) {
            return;
        }

        $categoryId = $this->request->get('id');

        /** @var Category $category */
        $category = $this->doctrineHelper->getEntityReference('OroB2BCatalogBundle:Category', $categoryId);
        if ($category) {
            $template = $event->getEnvironment()->render(
                'OroB2BAccountBundle:Category:account_category_visibility_edit.html.twig',
                ['entity' => $category]
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
        $blockLabel = $this->translator->trans('orob2b.account.categoryvisibility.visibility.label');
        $blockId = $scrollData->addBlock($blockLabel);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $html);
    }
}

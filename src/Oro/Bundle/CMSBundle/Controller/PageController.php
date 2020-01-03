<?php

namespace Oro\Bundle\CMSBundle\Controller;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Form\Type\PageType;
use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Bundle\RedirectBundle\Helper\ChangedSlugsHelper;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD actions for Page entity
 */
class PageController extends AbstractController
{
    /**
     * @Route("/view/{id}", name="oro_cms_page_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_cms_page_view",
     *      type="entity",
     *      class="OroCMSBundle:Page",
     *      permission="VIEW"
     * )
     *
     * @param Page $page
     * @return array
     */
    public function viewAction(Page $page)
    {
        return [
            'entity' => $page
        ];
    }

    /**
     * @Route("/info/{id}", name="oro_cms_page_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_cms_page_view")
     *
     * @param Page $page
     * @return array
     */
    public function infoAction(Page $page)
    {
        return [
            'entity' => $page
        ];
    }

    /**
     * @Route("/", name="oro_cms_page_index")
     * @Template
     * @AclAncestor("oro_cms_page_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => Page::class
        ];
    }

    /**
     * @Route("/create", name="oro_cms_page_create")
     * @Template("OroCMSBundle:Page:update.html.twig")
     *
     * @Acl(
     *      id="oro_cms_page_create",
     *      type="entity",
     *      class="OroCMSBundle:Page",
     *      permission="CREATE"
     * )
     *
     * @return array|RedirectResponse
     */
    public function createAction()
    {
        $page = new Page();
        return $this->update($page);
    }

    /**
     * @Route("/update/{id}", name="oro_cms_page_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_cms_page_update",
     *      type="entity",
     *      class="OroCMSBundle:Page",
     *      permission="EDIT"
     * )
     * @param Page $page
     * @return array|RedirectResponse
     */
    public function updateAction(Page $page)
    {
        return $this->update($page);
    }

    /**
     * @param Page $page
     * @return array|RedirectResponse
     */
    protected function update(Page $page)
    {
        return $this->get(UpdateHandler::class)->handleUpdate(
            $page,
            $this->createForm(PageType::class, $page),
            function (Page $page) {
                return [
                    'route' => 'oro_cms_page_update',
                    'parameters' => ['id' => $page->getId()]
                ];
            },
            function (Page $page) {
                return [
                    'route' => 'oro_cms_page_view',
                    'parameters' => ['id' => $page->getId()]
                ];
            },
            $page->getDraftUuid()
                ? $this->get(TranslatorInterface::class)->trans('oro.draft.operations.create.success')
                : $this->get(TranslatorInterface::class)->trans('oro.cms.controller.page.saved.message')
        );
    }

    /**
     * @Route("/get-changed-urls/{id}", name="oro_cms_page_get_changed_urls", requirements={"id"="\d+"})
     *
     * @AclAncestor("oro_cms_page_update")
     *
     * @param Page $page
     * @return JsonResponse
     */
    public function getChangedSlugsAction(Page $page)
    {
        return new JsonResponse($this->get(ChangedSlugsHelper::class)
            ->getChangedSlugsData($page, PageType::class));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            UpdateHandler::class,
            ChangedSlugsHelper::class,
            TranslatorInterface::class,
        ]);
    }
}

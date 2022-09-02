<?php

namespace Oro\Bundle\CMSBundle\Controller;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Form\Type\PageType;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
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
     */
    public function viewAction(Page $page): array
    {
        return [
            'entity' => $page
        ];
    }

    /**
     * @Route("/info/{id}", name="oro_cms_page_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_cms_page_view")
     */
    public function infoAction(Page $page): array
    {
        return [
            'entity' => $page
        ];
    }

    /**
     * @Route("/", name="oro_cms_page_index")
     * @Template
     * @AclAncestor("oro_cms_page_view")
     */
    public function indexAction(): array
    {
        return [
            'entity_class' => Page::class
        ];
    }

    /**
     * @Route("/create", name="oro_cms_page_create")
     * @Template("@OroCMS/Page/update.html.twig")
     *
     * @Acl(
     *      id="oro_cms_page_create",
     *      type="entity",
     *      class="OroCMSBundle:Page",
     *      permission="CREATE"
     * )
     */
    public function createAction(): array|RedirectResponse
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
     */
    public function updateAction(Page $page): array|RedirectResponse
    {
        return $this->update($page);
    }

    protected function update(Page $page): array|RedirectResponse
    {
        return $this->get(UpdateHandlerFacade::class)->update(
            $page,
            $this->createForm(PageType::class, $page),
            $page->getDraftUuid()
                ? $this->get(TranslatorInterface::class)->trans('oro.draft.operations.create.success')
                : $this->get(TranslatorInterface::class)->trans('oro.cms.controller.page.saved.message')
        );
    }

    /**
     * @Route("/get-changed-urls/{id}", name="oro_cms_page_get_changed_urls", requirements={"id"="\d+"})
     * @AclAncestor("oro_cms_page_update")
     */
    public function getChangedSlugsAction(Page $page): JsonResponse
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
            ChangedSlugsHelper::class,
            TranslatorInterface::class,
            UpdateHandlerFacade::class
        ]);
    }
}

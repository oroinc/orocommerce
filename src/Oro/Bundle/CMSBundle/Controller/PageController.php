<?php

namespace Oro\Bundle\CMSBundle\Controller;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Form\Type\PageType;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\RedirectBundle\Helper\ChangedSlugsHelper;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD actions for Page entity
 */
class PageController extends AbstractController
{
    #[Route(path: '/view/{id}', name: 'oro_cms_page_view', requirements: ['id' => '\d+'])]
    #[Template('@OroCMS/Page/view.html.twig')]
    #[Acl(id: 'oro_cms_page_view', type: 'entity', class: Page::class, permission: 'VIEW')]
    public function viewAction(Page $page): array
    {
        return [
            'entity' => $page
        ];
    }

    #[Route(path: '/info/{id}', name: 'oro_cms_page_info', requirements: ['id' => '\d+'])]
    #[Template('@OroCMS/Page/info.html.twig')]
    #[AclAncestor('oro_cms_page_view')]
    public function infoAction(Page $page): array
    {
        return [
            'entity' => $page
        ];
    }

    #[Route(path: '/', name: 'oro_cms_page_index')]
    #[Template('@OroCMS/Page/index.html.twig')]
    #[AclAncestor('oro_cms_page_view')]
    public function indexAction(): array
    {
        return [
            'entity_class' => Page::class
        ];
    }

    #[Route(path: '/create', name: 'oro_cms_page_create')]
    #[Template('@OroCMS/Page/update.html.twig')]
    #[Acl(id: 'oro_cms_page_create', type: 'entity', class: Page::class, permission: 'CREATE')]
    public function createAction(): array|RedirectResponse
    {
        $page = new Page();
        return $this->update($page);
    }

    #[Route(path: '/update/{id}', name: 'oro_cms_page_update', requirements: ['id' => '\d+'])]
    #[Template('@OroCMS/Page/update.html.twig')]
    #[Acl(id: 'oro_cms_page_update', type: 'entity', class: Page::class, permission: 'EDIT')]
    public function updateAction(Page $page): array|RedirectResponse
    {
        return $this->update($page);
    }

    protected function update(Page $page): array|RedirectResponse
    {
        return $this->container->get(UpdateHandlerFacade::class)->update(
            $page,
            $this->createForm(PageType::class, $page),
            $page->getDraftUuid()
                ? $this->container->get(TranslatorInterface::class)->trans('oro.draft.operations.create.success')
                : $this->container->get(TranslatorInterface::class)->trans('oro.cms.controller.page.saved.message')
        );
    }

    #[Route(path: '/get-changed-urls/{id}', name: 'oro_cms_page_get_changed_urls', requirements: ['id' => '\d+'])]
    #[AclAncestor('oro_cms_page_update')]
    public function getChangedSlugsAction(Page $page): JsonResponse
    {
        return new JsonResponse($this->container->get(ChangedSlugsHelper::class)
            ->getChangedSlugsData($page, PageType::class));
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            ChangedSlugsHelper::class,
            TranslatorInterface::class,
            UpdateHandlerFacade::class
        ]);
    }
}

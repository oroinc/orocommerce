<?php

namespace Oro\Bundle\CMSBundle\Controller\Frontend;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LayoutBundle\Attribute\Layout;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Landing page frontend controller.
 */
class PageController extends AbstractController
{
    /**
     *
     * @param Page $page
     * @return array
     */
    #[Route(path: '/view/{id}', name: 'oro_cms_frontend_page_view', requirements: ['id' => '\d+'])]
    #[Layout]
    #[Acl(id: 'oro_cms_frontend_page_view', type: 'entity', class: Page::class, permission: 'VIEW', groupName: '')]
    public function viewAction(Page $page)
    {
        return ['data' => ['page' => $page]];
    }
}

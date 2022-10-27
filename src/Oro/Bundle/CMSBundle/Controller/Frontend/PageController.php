<?php

namespace Oro\Bundle\CMSBundle\Controller\Frontend;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Landing page frontend controller.
 */
class PageController extends AbstractController
{
    /**
     * @Route("/view/{id}", name="oro_cms_frontend_page_view", requirements={"id"="\d+"})
     * @Layout()
     * @Acl(
     *      id="oro_cms_frontend_page_view",
     *      type="entity",
     *      class="OroCMSBundle:Page",
     *      permission="VIEW",
     *      group_name=""
     * )
     *
     * @param Page $page
     *
     * @return array
     */
    public function viewAction(Page $page)
    {
        return ['data' => ['page' => $page]];
    }
}

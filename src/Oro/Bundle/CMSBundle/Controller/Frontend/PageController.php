<?php

namespace Oro\Bundle\CMSBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\CMSBundle\Entity\Page;

class PageController extends Controller
{
    /**
     * @Route("/view/{id}", name="oro_cms_frontend_page_view", requirements={"id"="\d+"})
     * @Layout()
     *
     * @param Page $page
     * @return array
     */
    public function viewAction(Page $page)
    {
        return ['data'=> ['page' => $page]];
    }
}

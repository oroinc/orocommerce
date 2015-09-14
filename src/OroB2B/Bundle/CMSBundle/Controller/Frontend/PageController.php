<?php

namespace OroB2B\Bundle\CMSBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use OroB2B\Bundle\CMSBundle\Entity\Page;

class PageController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_cms_frontend_page_view", requirements={"id"="\d+"})
     * @Template("OroB2BCMSBundle:Page/Frontend:view.html.twig")
     *
     * @param Page $page
     * @return array
     */
    public function viewAction(Page $page)
    {
        return ['entity' => $page];
    }
}

<?php

namespace OroB2B\Bundle\WebsiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class WebsiteController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_website_view", requirements={"id"="\d+"})
     * @Acl(
     *      id="orob2b_website_view",
     *      type="entity",
     *      class="OroB2BWebsiteBundle:Website",
     *      permission="VIEW"
     * )
     *
     * @param Website $website
     * @return Response
     */
    public function viewAction(Website $website)
    {
        // TODO: Implement view action
        return new Response($website->getName());
    }

    /**
     * @Route("/", name="orob2b_website_index")
     * @Template
     * @AclAncestor("orob2b_website_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_website.website.class')
        ];
    }
}

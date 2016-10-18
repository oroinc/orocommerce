<?php

namespace Oro\Bundle\WebCatalogBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class WebCatalogController extends Controller
{
    /**
     * @Route("/", name="oro_web_catalog_index")
     * @Template
     * @AclAncestor("oro_web_catalog_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => WebCatalog::class
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_web_catalog_view", requirements={"id"="\d+"})
     *
     * @Acl(
     *      id="oro_web_catalog_view",
     *      type="entity",
     *      class="OroWebCatalogBundle:WebCatalog",
     *      permission="VIEW"
     * )
     * @Template()
     *
     * @param WebCatalog $webCatalog
     * @return array
     */
    public function viewAction(WebCatalog $webCatalog)
    {
        return [
            'entity' => $webCatalog
        ];
    }

    /**
     * @Route("/create", name="oro_web_catalog_create")
     * @Template("OroWebCatalogBundle:WebCatalog:update.html.twig")
     * @Acl(
     *      id="oro_web_catalog_create",
     *      type="entity",
     *      class="OroWebCatalogBundle:WebCatalog",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        return new Response('Will be done in scope BB-3306');
    }

    /**
     * @Route("/update/{id}", name="oro_web_catalog_update", requirements={"id"="\d+"})
     *
     * @Acl(
     *      id="oro_web_catalog_update",
     *      type="entity",
     *      class="OroWebCatalogBundle:WebCatalog",
     *      permission="EDIT"
     * )
     * @Template()
     *
     * @param WebCatalog $webCatalog
     * @return array
     */
    public function updateAction(WebCatalog $webCatalog)
    {
        return new Response('Will be done in scope BB-3306');
    }
}

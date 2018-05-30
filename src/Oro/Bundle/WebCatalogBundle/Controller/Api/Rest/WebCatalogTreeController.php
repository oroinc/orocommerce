<?php

namespace Oro\Bundle\WebCatalogBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\NamePrefix("oro_api_webcatalog_tree_")
 */
class WebCatalogTreeController extends FOSRestController
{
    /**
     * Retrieve a specific record.
     *
     * @param WebCatalog $webCatalog
     *
     * @Rest\Get(
     *      "/api/rest/{version}/webcatalog/{webCatalog}/tree",
     *     defaults={"version"="latest", "_format"="json"}
     * )
     * @ApiDoc(description="Get content node tree by web catalog", resource=true)
     * @AclAncestor("oro_web_catalog_view")
     *
     * @return Response
     */
    public function getAction(WebCatalog $webCatalog)
    {
        $treeHandler = $this->getTreeHandler();
        $root = $treeHandler->getTreeRootByWebCatalog($webCatalog);
        $tree = $treeHandler->createTree($root, true);

        return $this->handleView(
            $this->view($tree, Codes::HTTP_OK)
        );
    }

    /**
     * @return ContentNodeTreeHandler
     */
    protected function getTreeHandler()
    {
        return $this->container->get('oro_web_catalog.content_node_tree_handler');
    }
}

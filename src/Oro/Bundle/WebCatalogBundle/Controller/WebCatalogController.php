<?php

namespace Oro\Bundle\WebCatalogBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Form\Type\ContentNodeType;
use Oro\Bundle\WebCatalogBundle\Form\Type\WebCatalogType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
        return $this->update(new WebCatalog());
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
        return $this->update($webCatalog);
    }

    /**
     * @param WebCatalog $webCatalog
     * @return array|RedirectResponse
     */
    protected function update(WebCatalog $webCatalog)
    {
        $form = $this->createForm(WebCatalogType::NAME, $webCatalog);

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $webCatalog,
            $form,
            function (WebCatalog $webCatalog) {
                return [
                    'route' => 'oro_web_catalog_update',
                    'parameters' => ['id' => $webCatalog->getId()]
                ];
            },
            function (WebCatalog $webCatalog) {
                return [
                    'route' => 'oro_web_catalog_view',
                    'parameters' => ['id' => $webCatalog->getId()]
                ];
            },
            $this->get('translator')->trans('oro.webcatalog.controller.webcatalog.saved.message')
        );
    }

    /**
     * @Route("/update-tree/{id}", name="oro_web_catalog_update_tree", requirements={"id"="\d+"})
     *
     * @AclAncestor("oro_web_catalog_update")
     * @Template("OroWebCatalogBundle:WebCatalog:update_tree.html.twig")
     *
     * @param WebCatalog $webCatalog
     * @return array
     */
    public function updateTreeRootAction(WebCatalog $webCatalog)
    {
        $rootNode = $this->get('oro_web_catalog.content_node_tree_handler')->getTreeRootByWebCatalog($webCatalog);
        if (!$rootNode) {
            $rootNode = new ContentNode();
            $rootNode->setWebCatalog($webCatalog);
        }

        return $this->updateTreeNode($rootNode);
    }

    /**
     * @Route("/update-tree/node/{id}", name="oro_web_catalog_update_tree_node", requirements={"id"="\d+"})
     *
     * @AclAncestor("oro_web_catalog_update")
     * @Template("OroWebCatalogBundle:WebCatalog:update_tree.html.twig")
     *
     * @param ContentNode $contentNode
     * @return array
     */
    public function updateTreeNodeAction(ContentNode $contentNode)
    {
        return $this->updateTreeNode($contentNode);
    }

    /**
     * @param ContentNode $node
     * @return array|RedirectResponse
     */
    protected function updateTreeNode(ContentNode $node)
    {
        $form = $this->createForm(ContentNodeType::NAME, $node);

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $node,
            $form,
            function (ContentNode $node) {
                return [
                    'route' => 'oro_web_catalog_update_tree_node',
                    'parameters' => ['id' => $node->getId()]
                ];
            },
            function (ContentNode $node) {
                return [
                    'route' => 'oro_web_catalog_update_tree_node',
                    'parameters' => ['id' => $node->getId()]
                ];
            },
            $this->get('translator')->trans('oro.webcatalog.controller.webcatalog.saved.message')
        );
    }
}

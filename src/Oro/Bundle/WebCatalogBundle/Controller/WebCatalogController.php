<?php

namespace Oro\Bundle\WebCatalogBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\UIBundle\Form\Type\TreeMoveType;
use Oro\Bundle\UIBundle\Model\TreeCollection;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Form\Type\WebCatalogType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

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
     * @Route("/move/{id}", name="oro_web_catalog_move")
     * @Template
     * @Acl(
     *      id="oro_web_catalog_update",
     *      type="entity",
     *      class="OroWebCatalogBundle:WebCatalog",
     *      permission="EDIT"
     * )
     *
     * @param Request $request
     * @param WebCatalog $webCatalog
     * @return array
     */
    public function moveAction(Request $request, WebCatalog $webCatalog)
    {
        $handler = $this->get('oro_web_catalog.content_node_tree_handler');

        $root = $handler->getTreeRootByWebCatalog($webCatalog);
        $treeItems = $handler->getTreeItemList($root, true);

        $selected = $request->get('selected', []);

        $collection = new TreeCollection();
        $collection->source = array_intersect_key($treeItems, array_flip($selected));

        $treeData = $handler->createTree($root, true);
        $handler->disableTreeItems($collection->source, $treeData);
        $form = $this->createForm(TreeMoveType::class, $collection, [
            'tree_items' => $treeItems,
            'tree_data' => $treeData,
        ]);

        $responseData = ['treeItems' => $treeItems];

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $changed = [];
            $currentInsertPosition = count($collection->target->getChildren());
            foreach ($collection->source as $source) {
                $handler->moveNode($source->getKey(), $collection->target->getKey(), $currentInsertPosition);
                $changed[] = [
                    'id' => $source->getKey(),
                    'parent' => $collection->target->getKey(),
                    'position' => $currentInsertPosition
                ];
                $currentInsertPosition++;
            }

            $responseData['saved'] = true;
            $responseData['changed'] = $changed;
        }

        $responseData['form'] = $form->createView();

        return $responseData;
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
}

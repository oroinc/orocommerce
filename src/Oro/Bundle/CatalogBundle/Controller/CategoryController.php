<?php

namespace Oro\Bundle\CatalogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Form\Handler\CategoryHandler;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\UIBundle\Form\Type\TreeMoveType;
use Oro\Bundle\UIBundle\Model\TreeCollection;

class CategoryController extends Controller
{
    /**
     * @Route("/create/{id}", name="oro_catalog_category_create", requirements={"id"="\d+"})
     * @Template("OroCatalogBundle:Category:update.html.twig")
     * @Acl(
     *      id="oro_catalog_category_create",
     *      type="entity",
     *      class="OroCatalogBundle:Category",
     *      permission="CREATE"
     * )
     * @param Category $parentCategory
     * @return array|RedirectResponse
     */
    public function createAction(Category $parentCategory)
    {
        $category = new Category();
        $category->setParentCategory($parentCategory);

        return $this->update($category);
    }

    /**
     * @Route("/update/{id}", name="oro_catalog_category_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_catalog_category_update",
     *      type="entity",
     *      class="OroCatalogBundle:Category",
     *      permission="EDIT"
     * )
     * @param Category $category
     * @return array|RedirectResponse
     */
    public function updateAction(Category $category)
    {
        return $this->update($category);
    }

    /**
     * @Route("/", name="oro_catalog_category_index")
     * @Template
     * @Acl(
     *      id="oro_catalog_category_view",
     *      type="entity",
     *      class="OroCatalogBundle:Category",
     *      permission="VIEW"
     * )
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'rootCategory' => $this->getMasterRootCategory()
        ];
    }

    /**
     * @Route("/move", name="oro_catalog_category_move_form")
     * @Template
     * @Acl(
     *      id="oro_catalog_category_update",
     *      type="entity",
     *      class="OroCatalogBundle:Category",
     *      permission="EDIT"
     * )
     *
     * @param Request $request
     *
     * @return array
     */
    public function moveAction(Request $request)
    {
        $handler = $this->get('oro_catalog.category_tree_handler');

        $root = $this->getMasterRootCategory();
        $treeItems = $handler->getTreeItemList($root, true);

        $collection = new TreeCollection();
        $collection->source = array_intersect_key($treeItems, array_flip($request->get('selected', [])));

        $treeData = $handler->createTree($root, true);
        $handler->disableTreeItems($collection->source, $treeData);
        $form = $this->createForm(TreeMoveType::class, $collection, [
            'tree_data' => $treeData,
            'tree_items' => $treeItems,
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

            $response['saved'] = true;
        }

        return array_merge($responseData, ['form' => $form->createView()]);
    }

    /**
     * @Route("/widget/tree", name="oro_catalog_category_tree_widget")
     * @Template
     * @Acl(
     *      id="oro_catalog_category_view",
     *      type="entity",
     *      class="OroCatalogBundle:Category",
     *      permission="VIEW"
     * )
     *
     * @return array
     */
    public function treeWidgetAction()
    {
        return [];
    }

    /**
     * @param Category $category
     * @return array|RedirectResponse
     */
    protected function update(Category $category)
    {
        $form = $this->createForm(CategoryType::NAME, $category);
        $handler = new CategoryHandler(
            $form,
            $this->getRequest(),
            $this->getDoctrine()->getManagerForClass('OroCatalogBundle:Category'),
            $this->get('event_dispatcher')
        );

        $result = $this->get('oro_form.model.update_handler')->handleUpdate(
            $category,
            $form,
            function (Category $category) {
                return [
                    'route' => 'oro_catalog_category_update',
                    'parameters' => ['id' => $category->getId()]
                ];
            },
            function () {
                return [
                    'route' => 'oro_catalog_category_index',
                ];
            },
            $this->get('translator')->trans('oro.catalog.controller.category.saved.message'),
            $handler
        );

        if (is_array($result)) {
            $result['rootCategory'] = $this->getMasterRootCategory();
        }

        return $result;
    }

    /**
     * @return Category
     */
    protected function getMasterRootCategory()
    {
        return $this->getDoctrine()->getRepository('OroCatalogBundle:Category')->getMasterCatalogRoot();
    }
}

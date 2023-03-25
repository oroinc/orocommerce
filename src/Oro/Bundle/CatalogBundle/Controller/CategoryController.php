<?php

namespace Oro\Bundle\CatalogBundle\Controller;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Form\Handler\CategoryHandler;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;
use Oro\Bundle\CatalogBundle\JsTree\CategoryTreeHandler;
use Oro\Bundle\CatalogBundle\Provider\CategoryFormTemplateDataProvider;
use Oro\Bundle\CatalogBundle\Provider\MasterCatalogRootProvider;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\RedirectBundle\Helper\ChangedSlugsHelper;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\UIBundle\Form\Type\TreeMoveType;
use Oro\Bundle\UIBundle\Model\TreeCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD controller for Category entity
 */
class CategoryController extends AbstractController
{
    /**
     * @Route("/create/{id}", name="oro_catalog_category_create", requirements={"id"="\d+"})
     * @Template("@OroCatalog/Category/update.html.twig")
     * @Acl(
     *      id="oro_catalog_category_create",
     *      type="entity",
     *      class="OroCatalogBundle:Category",
     *      permission="CREATE"
     * )
     */
    public function createAction(Category $parentCategory, Request $request): array|RedirectResponse
    {
        $category = new Category();
        $category->setParentCategory($parentCategory);

        return $this->update($category, $request);
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
     */
    public function updateAction(Category $category, Request $request): array|RedirectResponse
    {
        return $this->update($category, $request);
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
     */
    public function indexAction(): array
    {
        return ['rootCategory' => $this->get(MasterCatalogRootProvider::class)->getMasterCatalogRoot()];
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
     */
    public function moveAction(Request $request): array
    {
        $handler = $this->get(CategoryTreeHandler::class);

        $root = $this->get(MasterCatalogRootProvider::class)->getMasterCatalogRoot();
        $treeItems = $handler->getTreeItemList($root, true);

        $collection = new TreeCollection();
        $collection->source = array_intersect_key($treeItems, array_flip($request->get('selected', [])));

        $treeData = $handler->createTree($root, true);
        $handler->disableTreeItems($collection->source, $treeData);
        $form = $this->createForm(TreeMoveType::class, $collection, [
            'tree_data' => $treeData,
            'tree_items' => $treeItems,
        ]);

        $responseData = [
            'treeItems' => $treeItems,
            'changed' => [],
        ];

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $currentInsertPosition = count($collection->target->getChildren());
            foreach ($collection->source as $source) {
                $handler->moveNode($source->getKey(), $collection->target->getKey(), $currentInsertPosition);
                $responseData['changed'][] = [
                    'id' => $source->getKey(),
                    'parent' => $collection->target->getKey(),
                    'position' => $currentInsertPosition,
                ];
                $currentInsertPosition++;
            }

            $responseData['saved'] = true;
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
     */
    public function treeWidgetAction(): array
    {
        return [];
    }

    protected function update(Category $category, Request $request): array|RedirectResponse
    {
        $form = $this->createForm(CategoryType::class, $category);
        $handler = new CategoryHandler(
            $this->getDoctrine()->getManagerForClass('OroCatalogBundle:Category'),
            $this->get(EventDispatcherInterface::class)
        );

        $result = $this->get(UpdateHandlerFacade::class)->update(
            $category,
            $form,
            $this->get(TranslatorInterface::class)->trans('oro.catalog.controller.category.saved.message'),
            $request,
            $handler,
            $this->get(CategoryFormTemplateDataProvider::class)
        );

        if (is_array($result)) {
            $result['rootCategory'] = $this->get(MasterCatalogRootProvider::class)
                ->getMasterCatalogRoot();
        }

        return $result;
    }

    /**
     * @Route("/get-changed-urls/{id}", name="oro_catalog_category_get_changed_slugs", requirements={"id"="\d+"})
     * @AclAncestor("oro_catalog_category_update")
     */
    public function getChangedSlugsAction(Category $category): JsonResponse
    {
        return new JsonResponse(
            $this->get(ChangedSlugsHelper::class)
                ->getChangedSlugsData($category, CategoryType::class)
        );
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            CategoryTreeHandler::class,
            MasterCatalogRootProvider::class,
            ChangedSlugsHelper::class,
            EventDispatcherInterface::class,
            TranslatorInterface::class,
            UpdateHandlerFacade::class,
            CategoryFormTemplateDataProvider::class,
        ]);
    }
}

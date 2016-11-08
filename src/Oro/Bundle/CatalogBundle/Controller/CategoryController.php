<?php

namespace Oro\Bundle\CatalogBundle\Controller;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Form\Handler\CategoryHandler;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
                return array(
                    'route' => 'oro_catalog_category_update',
                    'parameters' => array('id' => $category->getId())
                );
            },
            function () {
                return array(
                    'route' => 'oro_catalog_category_index',
                );
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

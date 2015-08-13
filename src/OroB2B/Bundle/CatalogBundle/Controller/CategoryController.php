<?php

namespace OroB2B\Bundle\CatalogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Form\Handler\CategoryHandler;
use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryType;

class CategoryController extends Controller
{
    /**
     * @Route("/create/{id}", name="orob2b_catalog_category_create", requirements={"id"="\d+"})
     * @Template("OroB2BCatalogBundle:Category:update.html.twig")
     * @Acl(
     *      id="orob2b_catalog_category_create",
     *      type="entity",
     *      class="OroB2BCatalogBundle:Category",
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
     * @Route("/update/{id}", name="orob2b_catalog_category_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_catalog_category_update",
     *      type="entity",
     *      class="OroB2BCatalogBundle:Category",
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
     * @Route("/", name="orob2b_catalog_category_index")
     * @Template
     * @Acl(
     *      id="orob2b_catalog_category_view",
     *      type="entity",
     *      class="OroB2BCatalogBundle:Category",
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
        $form = $this->createForm(CategoryType::NAME);
        $handler = new CategoryHandler(
            $form,
            $this->getRequest(),
            $this->getDoctrine()->getManagerForClass('OroB2BCatalogBundle:Category'),
            $this->get('event_dispatcher')
        );

        $result = $this->get('oro_form.model.update_handler')->handleUpdate(
            $category,
            $form,
            function (Category $category) {
                return array(
                    'route' => 'orob2b_catalog_category_update',
                    'parameters' => array('id' => $category->getId())
                );
            },
            function () {
                return array(
                    'route' => 'orob2b_catalog_category_index',
                );
            },
            $this->get('translator')->trans('orob2b.catalog.controller.category.saved.message'),
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
        return $this->getDoctrine()->getRepository('OroB2BCatalogBundle:Category')->getMasterCatalogRoot();
    }
}

<?php

namespace Oro\Bundle\CatalogBundle\Controller;

use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Form;

/**
 * Contains base actions for Product
 */
class BaseProductController extends Controller
{
    /**
     * @return Form
     */
    protected function createIncludeSubcategoriesForm()
    {
        return $this->createForm(
            CheckboxType::class,
            null,
            [
                'label' => 'oro.catalog.category.include_subcategories.label',
                'required' => false,
                'data' => $this->getCatalogRequestHandler()->getIncludeSubcategoriesChoice(),
            ]
        );
    }

    /**
     * @return Form
     */
    protected function createIncludeNotCategorizedProductForm()
    {
        return $this->createForm(
            CheckboxType::class,
            null,
            [
                'label' => 'oro.catalog.category.include_not_categorized_products.label',
                'required' => false,
                'data' => $this->getCatalogRequestHandler()->getIncludeNotCategorizedProductsChoice(),
            ]
        );
    }

    /**
     * @return RequestProductHandler
     */
    protected function getCatalogRequestHandler()
    {
        return $this->get('oro_catalog.handler.request_product');
    }

    /**
     * @return array
     */
    public function sidebarAction()
    {
        $masterCatalogRoot = $this->get('oro_catalog.provider.master_catalog_root')
            ->getMasterCatalogRootByOrganization();

        return [
            'defaultCategoryId' => $this->getCatalogRequestHandler()->getCategoryId(),
            'includeSubcategoriesForm' => $this->createIncludeSubcategoriesForm()->createView(),
            'includeNotCategorizedProductForm' => $this->createIncludeNotCategorizedProductForm()->createView(),
            'rootCategory' => $masterCatalogRoot
        ];
    }
}

<?php

namespace OroB2B\Bundle\CatalogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class BaseProductController extends Controller
{
    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createIncludeSubcategoriesForm()
    {
        return $this->createForm(
            'checkbox',
            null,
            [
                'label' => 'orob2b.catalog.category.include_subcategories.label',
                'required' => false,
                'data' => $this->getCatalogRequestHandler()->getIncludeSubcategoriesChoice(),
            ]
        );
    }

    /**
     * @return \OroB2B\Bundle\CatalogBundle\Handler\RequestProductHandler
     */
    protected function getCatalogRequestHandler()
    {
        return $this->get('orob2b_catalog.handler.request_product');
    }

    /**
     * @return array
     */
    public function sidebarAction()
    {
        return [
            'defaultCategoryId' => $this->getCatalogRequestHandler()->getCategoryId(),
            'includeSubcategoriesForm' => $this->createIncludeSubcategoriesForm()->createView()
        ];
    }
}

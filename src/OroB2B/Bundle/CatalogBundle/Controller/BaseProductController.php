<?php

namespace OroB2B\Bundle\CatalogBundle\Controller;

use OroB2B\Bundle\CatalogBundle\Handler\RequestProductHandler;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;

class BaseProductController extends Controller
{
    /**
     * @return Form
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
     * @return RequestProductHandler
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

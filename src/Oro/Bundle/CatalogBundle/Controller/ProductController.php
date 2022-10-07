<?php

namespace Oro\Bundle\CatalogBundle\Controller;

use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Provider\MasterCatalogRootProvider;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Product sidebar controller
 */
class ProductController extends AbstractController
{
    /**
     * @Route("/sidebar", name="oro_catalog_category_product_sidebar")
     * @AclAncestor("oro_catalog_category_view")
     * @Template
     *
     * @return array
     */
    public function sidebarAction()
    {
        $catalogRequestHandler = $this->get(RequestProductHandler::class);

        $includeSubcategoriesForm = $this->createForm(
            CheckboxType::class,
            null,
            [
                'label' => 'oro.catalog.category.include_subcategories.label',
                'required' => false,
                'data' => $catalogRequestHandler->getIncludeSubcategoriesChoice(),
            ]
        );

        $includeNotCategorizedProductForm = $this->createForm(
            CheckboxType::class,
            null,
            [
                'label' => 'oro.catalog.category.include_not_categorized_products.label',
                'required' => false,
                'data' => $catalogRequestHandler->getIncludeNotCategorizedProductsChoice(),
            ]
        );

        $masterCatalogRoot = $this->get(MasterCatalogRootProvider::class)
            ->getMasterCatalogRoot();

        return [
            'defaultCategoryId' => $catalogRequestHandler->getCategoryId(),
            'includeSubcategoriesForm' => $includeSubcategoriesForm->createView(),
            'includeNotCategorizedProductForm' => $includeNotCategorizedProductForm->createView(),
            'rootCategory' => $masterCatalogRoot
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            RequestProductHandler::class,
            MasterCatalogRootProvider::class,
        ]);
    }
}

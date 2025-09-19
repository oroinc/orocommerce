<?php

namespace Oro\Bundle\CatalogBundle\Controller;

use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Provider\MasterCatalogRootProvider;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Product sidebar controller
 */
class ProductController extends AbstractController
{
    /**
     * @return array
     */
    #[Route(path: '/sidebar', name: 'oro_catalog_category_product_sidebar')]
    #[Template('@OroCatalog/Product/sidebar.html.twig')]
    #[AclAncestor('oro_catalog_category_view')]
    public function sidebarAction()
    {
        $catalogRequestHandler = $this->container->get(RequestProductHandler::class);

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

        $masterCatalogRoot = $this->container->get(MasterCatalogRootProvider::class)
            ->getMasterCatalogRoot();

        return [
            'defaultCategoryId' => $catalogRequestHandler->getCategoryId(),
            'includeSubcategoriesForm' => $includeSubcategoriesForm->createView(),
            'includeNotCategorizedProductForm' => $includeNotCategorizedProductForm->createView(),
            'rootCategory' => $masterCatalogRoot
        ];
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            RequestProductHandler::class,
            MasterCatalogRootProvider::class,
        ]);
    }
}

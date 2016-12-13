<?php

namespace Oro\Bundle\CatalogBundle\Layout\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductCategoriesContextConfigurator implements ContextConfiguratorInterface
{
    const OPTION_NAME = 'category_ids';
    const PRODUCT_LIST_ROUTE = 'oro_product_frontend_product_index';
    const PRODUCT_VIEW_ROUTE = 'oro_product_frontend_product_view';

    /**
     * @var  RequestStack
     */
    protected $requestStack;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var CategoryProvider
     */
    protected $categoryProvider;


    /**
     * @param RequestStack $requestStack
     * @param ManagerRegistry $registry
     * @param CategoryProvider $categoryProvider
     */
    public function __construct(
        RequestStack $requestStack,
        ManagerRegistry $registry,
        CategoryProvider $categoryProvider
    ) {
        $this->requestStack = $requestStack;
        $this->registry = $registry;
        $this->categoryProvider = $categoryProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $request = $this->requestStack->getCurrentRequest();

        $allowedRoutes = [self::PRODUCT_LIST_ROUTE, self::PRODUCT_VIEW_ROUTE];
        if (!in_array($request->attributes->get('_route'), $allowedRoutes)) {
            return;
        }

        $currentCategory = null;

        if ($request->attributes->get('_route') == self::PRODUCT_LIST_ROUTE) {
            $currentCategory = $this->categoryProvider->getCurrentCategory();
        } elseif ($request->attributes->get('_route') == self::PRODUCT_VIEW_ROUTE) {
            $routeParams = $request->attributes->get('_route_params');

            $product = $this->registry
                ->getManagerForClass(Product::class)
                ->getRepository(Product::class)
                ->find((int)$routeParams['id']);

            $currentCategory = $this->registry
                ->getManagerForClass(Category::class)
                ->getRepository(Category::class)
                ->findOneByProduct($product);
        }

        $categoryIds = [];

        if ($currentCategory !== null) {
            $categoryIds = array_merge([$currentCategory->getId()], $this->getParentCategoryIds($currentCategory));
        }

        $context->getResolver()->setDefined(self::OPTION_NAME);
        $context->set(self::OPTION_NAME, $categoryIds);
    }

    /**
     * @param Category $category
     * @return array
     */
    protected function getParentCategoryIds(Category $category)
    {
        $parentCategoryIds = [];
        $currentCategory = $category->getParentCategory();

        while ($currentCategory) {
            $parentCategoryIds[] = $currentCategory->getId();
            $currentCategory = $currentCategory->getParentCategory();
        }

        return $parentCategoryIds;
    }
}

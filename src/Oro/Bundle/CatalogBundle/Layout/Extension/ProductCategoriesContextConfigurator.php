<?php

namespace Oro\Bundle\CatalogBundle\Layout\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Configure category context based on current category or currently requested product
 */
class ProductCategoriesContextConfigurator implements ContextConfiguratorInterface
{
    const CATEGORY_IDS_OPTION_NAME = 'category_ids';
    const CATEGORY_ID_OPTION_NAME = 'category_id';
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
        if (!$request) {
            return;
        }

        $allowedRoutes = [self::PRODUCT_LIST_ROUTE, self::PRODUCT_VIEW_ROUTE];
        if (!\in_array($request->attributes->get('_route'), $allowedRoutes, true)) {
            return;
        }

        /** @var Category $currentCategory */
        $currentCategory = null;

        if ($request->attributes->get('_route') === self::PRODUCT_LIST_ROUTE) {
            $currentCategory = $this->categoryProvider->getCurrentCategory();
        } elseif ($request->attributes->get('_route') === self::PRODUCT_VIEW_ROUTE) {
            $routeParams = $request->attributes->get('_route_params');

            $product = $this->registry
                ->getManagerForClass(Product::class)
                ->getRepository(Product::class)
                ->find((int)$routeParams['id']);

            if (!$product) {
                return;
            }
            /** @var CategoryRepository $categoryRepository */
            $categoryRepository = $this->registry
                ->getManagerForClass(Category::class)
                ->getRepository(Category::class);

            $currentCategory = $categoryRepository->findOneByProduct($product);
        }

        $categoryIds = [];

        if ($currentCategory !== null) {
            $categoryIds = array_merge([$currentCategory->getId()], $this->getParentCategoryIds($currentCategory));
        }

        $context->getResolver()->setDefined(self::CATEGORY_IDS_OPTION_NAME);
        $context->getResolver()->setDefined(self::CATEGORY_ID_OPTION_NAME);

        $context->set(self::CATEGORY_IDS_OPTION_NAME, $categoryIds);
        $context->set(self::CATEGORY_ID_OPTION_NAME, $currentCategory !== null ? $currentCategory->getId() : null);
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

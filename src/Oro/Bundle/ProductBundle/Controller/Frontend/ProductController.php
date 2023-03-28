<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Layout\AttributeRenderRegistry;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\PricingBundle\Form\Extension\PriceAttributesProductFormExtension;
use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductViewFormAvailabilityProvider;
use Oro\Bundle\ProductBundle\Provider\PageTemplateProvider;
use Oro\Bundle\ProductBundle\Provider\ProductAutocompleteProvider;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for the product view and search functionality.
 */
class ProductController extends AbstractController
{
    /**
     * View list of products
     *
     * @Route("/", name="oro_product_frontend_product_index")
     * @Layout(vars={"entity_class", "grid_config", "theme_name", "filters_position"})
     * @AclAncestor("oro_product_frontend_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => Product::class,
            'theme_name' => $this->get(DataGridThemeHelper::class)
                ->getTheme('frontend-product-search-grid'),
            'grid_config' => [
                'frontend-product-search-grid'
            ],
            'filters_position' => $this->getFiltersPosition(),
        ];
    }

    /**
     * Search products
     *
     * @Route("/search", name="oro_product_frontend_product_search")
     * @Layout(vars={"entity_class", "grid_config", "theme_name", "filters_position"})
     * @AclAncestor("oro_product_frontend_view")
     *
     * @return array
     */
    public function searchAction()
    {
        return [
            'entity_class' => Product::class,
            'theme_name' => $this->get(DataGridThemeHelper::class)
                ->getTheme('frontend-product-search-grid'),
            'grid_config' => [
                'frontend-product-search-grid'
            ],
            'filters_position' => $this->getFiltersPosition(),
        ];
    }

    private function getFiltersPosition(): string
    {
        return $this->container->get(ConfigManager::class)->get(Configuration::getConfigKeyByName('filters_position'));
    }

    /**
     * Get data for website search autocomplete
     *
     * @Route("/search/autocomplete", name="oro_product_frontend_product_search_autocomplete")
     * @AclAncestor("oro_product_frontend_view")
     */
    public function autocompleteAction(Request $request): JsonResponse
    {
        $searchString = trim($request->get('search'));
        $searchSessionId = trim($request->get('search_id'));

        $autocompleteData = $this->get(ProductAutocompleteProvider::class)
            ->getAutocompleteData($searchString, $searchSessionId);

        return new JsonResponse($autocompleteData);
    }

    /**
     * View list of products
     *
     * @Route("/view/{id}", name="oro_product_frontend_product_view", requirements={"id"="\d+"})
     * @Layout(vars={"product_type", "attribute_family", "page_template"})
     * @Acl(
     *      id="oro_product_frontend_view",
     *      type="entity",
     *      class="OroProductBundle:Product",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     *
     * @param Request $request
     * @param Product $product
     *
     * @return array
     */
    public function viewAction(Request $request, Product $product)
    {
        $data = [
            'product' => $product,
            'parentProduct' => null,
            'chosenProductVariant' => null
        ];

        if (!$request->get('ignoreProductVariant', false)
            && $product->isConfigurable()
            && $this->isSimpleFormAvailable($product)
        ) {
            $productAvailabilityProvider = $this->get(ProductVariantAvailabilityProvider::class);
            $simpleProduct = $productAvailabilityProvider->getSimpleProductByVariantFields($product, [], false);
            $data['chosenProductVariant'] = $this->getChosenProductVariantFromRequest($request, $product);
            if ($simpleProduct) {
                $data['productVariant'] = $simpleProduct;
                $data['parentProduct'] = $product;
            }
        }

        /** @var Product|null $parentProduct */
        $parentProduct = null;
        $parentProductId = $request->get('parentProductId');
        if ($parentProductId) {
            $parentProduct = $this->getDoctrine()->getRepository(Product::class)->find($parentProductId);
        }

        $pageTemplate = $this->get(PageTemplateProvider::class)
            ->getPageTemplate($parentProduct ?? $product, 'oro_product_frontend_product_view');

        $this->get(AttributeRenderRegistry::class)->setAttributeRendered(
            $product->getAttributeFamily(),
            PriceAttributesProductFormExtension::PRODUCT_PRICE_ATTRIBUTES_PRICES
        );

        return [
            'data' => $data,
            'product_type' => $product->getType(),
            'attribute_family' => $product->getAttributeFamily(),
            'page_template' => $pageTemplate?->getKey()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            DataGridThemeHelper::class,
            ProductVariantAvailabilityProvider::class,
            PageTemplateProvider::class,
            AttributeRenderRegistry::class,
            ProductAutocompleteProvider::class,
            ProductViewFormAvailabilityProvider::class,
            ConfigManager::class,
        ]);
    }

    private function getChosenProductVariantFromRequest(Request $request, Product $product): ?Product
    {
        $variantProduct = null;
        $variantProductId = $request->get('variantProductId');
        if ($variantProductId) {
            /** @var EntityManagerInterface $em */
            $em = $this->get('doctrine')->getManagerForClass(Product::class);
            $variantProductId = (int)$variantProductId;
            $simpleProductIds = $this->get(ProductVariantAvailabilityProvider::class)
                ->getSimpleProductIdsByConfigurable([$product->getId()]);
            foreach ($simpleProductIds as $simpleProductId) {
                if ($simpleProductId === $variantProductId) {
                    $variantProduct = $em->getReference(Product::class, $simpleProductId);
                    break;
                }
            }
        }

        return $variantProduct;
    }

    private function isSimpleFormAvailable(Product $product): bool
    {
        return $this->get(ProductViewFormAvailabilityProvider::class)->isSimpleFormAvailable($product);
    }
}

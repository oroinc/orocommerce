<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Oro\Bundle\EntityConfigBundle\Layout\AttributeRenderRegistry;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\PricingBundle\Form\Extension\PriceAttributesProductFormExtension;
use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormAvailabilityProvider;
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
    const GRID_NAME = 'frontend-product-search-grid';

    /**
     * View list of products
     *
     * @Route("/", name="oro_product_frontend_product_index")
     * @Layout(vars={"entity_class", "grid_config", "theme_name"})
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
        ];
    }

    /**
     * Search products
     *
     * @Route("/search", name="oro_product_frontend_product_search")
     * @Layout(vars={"entity_class", "grid_config", "theme_name"})
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
        ];
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

        $autocompleteData = $this->get(ProductAutocompleteProvider::class)
            ->getAutocompleteData($request, $searchString);

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

        $ignoreProductVariants = $request->get('ignoreProductVariant', false);
        $isSimpleFormAvailable = $this
            ->get('oro_product.layout.data_provider.product_view_form_availability_provider')
            ->isSimpleFormAvailable($product);

        if (!$ignoreProductVariants && $product->isConfigurable() && $isSimpleFormAvailable) {
            $productAvailabilityProvider = $this->get(ProductVariantAvailabilityProvider::class);
            $simpleProduct = $productAvailabilityProvider->getSimpleProductByVariantFields($product, [], false);
            $data['chosenProductVariant'] = $this->getChosenProductVariantFromRequest($request, $product);
            if ($simpleProduct) {
                $data['productVariant'] = $simpleProduct;
                $data['parentProduct'] = $product;
            }
        }

        $parentProduct = null;
        $parentProductId = $request->get('parentProductId');
        if ($parentProductId) {
            /** @var Product $parentProduct */
            $parentProduct = $this->getDoctrine()
                ->getManagerForClass('OroProductBundle:Product')
                ->getRepository('OroProductBundle:Product')
                ->find($parentProductId);
        }

        $templateProduct = $parentProduct ? $parentProduct : $product;
        $pageTemplate = $this->get(PageTemplateProvider::class)
            ->getPageTemplate($templateProduct, 'oro_product_frontend_product_view');

        $this->get(AttributeRenderRegistry::class)->setAttributeRendered(
            $product->getAttributeFamily(),
            PriceAttributesProductFormExtension::PRODUCT_PRICE_ATTRIBUTES_PRICES
        );

        return [
            'data' => $data,
            'product_type' => $product->getType(),
            'attribute_family' => $product->getAttributeFamily(),
            'page_template' => $pageTemplate ? $pageTemplate->getKey() : null
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
            'oro_product.layout.data_provider.product_view_form_availability_provider'
                => ProductFormAvailabilityProvider::class,
        ]);
    }

    private function getChosenProductVariantFromRequest(Request $request, Product $product): ?Product
    {
        $variantProductId = $request->get('variantProductId');
        if ($variantProductId) {
            $simpleProducts = $this->get(ProductVariantAvailabilityProvider::class)
                ->getSimpleProductsByConfigurable([$product]);

            foreach ($simpleProducts as $simpleProduct) {
                if ($simpleProduct->getId() === (int)$variantProductId) {
                    return $simpleProduct;
                }
            }
        }

        return null;
    }
}

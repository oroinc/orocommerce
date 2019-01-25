<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\PricingBundle\Form\Extension\PriceAttributesProductFormExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for the product view and search functionality.
 */
class ProductController extends Controller
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
            'entity_class' => $this->container->getParameter('oro_product.entity.product.class'),
            'theme_name' => $this->container->get('oro_product.datagrid_theme_helper')
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
            'entity_class' => $this->container->getParameter('oro_product.entity.product.class'),
            'theme_name' => $this->container->get('oro_product.datagrid_theme_helper')
                ->getTheme('frontend-product-search-grid'),
            'grid_config' => [
                'frontend-product-search-grid'
            ],
        ];
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
        ];

        $ignoreProductVariants = $request->get('ignoreProductVariant', false);
        $isSimpleFormAvailable = $this
            ->get('oro_product.layout.data_provider.product_view_form_availability_provider')
            ->isSimpleFormAvailable($product);

        if (!$ignoreProductVariants && $product->isConfigurable() && $isSimpleFormAvailable) {
            $productAvailabilityProvider = $this->get('oro_product.provider.product_variant_availability_provider');
            $simpleProduct = $productAvailabilityProvider->getSimpleProductByVariantFields($product, [], false);
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
        $pageTemplate = $this->get('oro_product.provider.page_template_provider')
            ->getPageTemplate($templateProduct, 'oro_product_frontend_product_view');

        $this->get('oro_entity_config.attribute_render_registry')->setAttributeRendered(
            $product->getAttributeFamily(),
            PriceAttributesProductFormExtension::PRODUCT_PRICE_ATTRIBUTES_PRICES
        );

        return  [
            'data' => $data,
            'product_type' => $product->getType(),
            'attribute_family' => $product->getAttributeFamily(),
            'page_template' => $pageTemplate ? $pageTemplate->getKey() : null
        ];
    }
}

<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductController extends Controller
{
    /**
     * View list of products
     *
     * @Route("/", name="oro_product_frontend_product_index")
     * @Layout(vars={"entity_class", "grid_config"})
     * @AclAncestor("oro_product_frontend_view")
     *
     * @return array
     */
    public function indexAction()
    {
        $gridName = 'frontend-product-search-grid';
        $gridConfig = $this->get('oro_datagrid.datagrid.manager')->getConfigurationForGrid($gridName);

        return [
            'entity_class' => $this->container->getParameter('oro_product.entity.product.class'),
            'grid_config' => $gridConfig->toArray(),
        ];
    }

    /**
     * View list of products
     *
     * @Route("/view/{id}", name="oro_product_frontend_product_view", requirements={"id"="\d+"})
     * @Layout(vars={"product_type", "attribute_family"})
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
        $data = ['product' => $product];

        $ignoreProductVariants = $request->get('ignoreProductVariant', false);

        if (!$ignoreProductVariants && $product->isConfigurable()) {
            $productAvailabilityProvider = $this->get('oro_product.provider.product_variant_availability_provider');
            $simpleProduct = $productAvailabilityProvider->getSimpleProductByVariantFields($product, [], false);
            if ($simpleProduct) {
                $data['productVariant'] = $simpleProduct;
            }
        }

        return [
            'data' => $data,
            'product_type' => $product->getType(),
            'attribute_family' => $product->getAttributeFamily(),
        ];
    }

    /**
     * @Route("/info/{id}", name="oro_product_frontend_product_info", requirements={"id"="\d+"})
     * @Template("OroProductBundle:Product\Frontend\widget:info.html.twig")
     * @AclAncestor("oro_product_frontend_view")
     *
     * @param Product $product
     *
     * @return array
     */
    public function infoAction(Product $product)
    {
        return [
            'product' => $product
        ];
    }
}

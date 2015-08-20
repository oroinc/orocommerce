<?php

namespace OroB2B\Bundle\ProductBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductController extends Controller
{
    /**
     * View list of products
     *
     * @Route("/", name="orob2b_product_frontend_product_index")
     * @Template("OroB2BProductBundle:Product\Frontend:index.html.twig")
     * @AclAncestor("orob2b_product_view_products")
     *
     * @return array
     */
    public function indexAction()
    {
        $widgetRouteParameters = [
            'gridName' => 'frontend-products-grid',
            'renderParams' => [
                'enableFullScreenLayout' => 1,
                'enableViews' => 0
            ],
            'renderParamsTypes' => [
                'enableFullScreenLayout' => 'int',
                'enableViews' => 'int'
            ]
        ];

        return [
            'entity_class' => $this->container->getParameter('orob2b_product.product.class'),
            'widgetRouteParameters' => $widgetRouteParameters,
        ];
    }

    /**
     * View list of products
     *
     * @Route("/view/{id}", name="orob2b_product_frontend_product_view", requirements={"id"="\d+"})
     * @Template("OroB2BProductBundle:Product\Frontend:view.html.twig")
     * @AclAncestor("orob2b_product_view_products")
     *
     * @param Product $product
     *
     * @return array
     */
    public function viewAction(Product $product)
    {
        return [
            'entity' => $product
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_product_frontend_product_info", requirements={"id"="\d+"})
     * @Template("OroB2BProductBundle:Product\Frontend\widget:info.html.twig")
     * @AclAncestor("orob2b_product_view_products")
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

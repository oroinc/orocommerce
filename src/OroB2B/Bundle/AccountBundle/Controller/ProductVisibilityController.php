<?php

namespace OroB2B\Bundle\AccountBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductVisibilityController
{
    /**
     * @Route("/edit/{id}", name="orob2b_account_product_visibility_edit", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_product_visibility_edit")
     *
     * @param Product $product
     * @return array
     */
    public function editAction(Product $product)
    {
        return [
            'entity' => $product,
        ];
    }

    /**
     * @Route(
     *      "/edit/{productId}/website/{id}",
     *      name="orob2b_account_product_visibility_website",
     *      requirements={"productId"="\d+", "id"="\d+"}
     * )
     * @ParamConverter("product", options={"id" = "productId"})
     * @Template("OroB2BAccountBundle:ProductVisibility/widget:website.html.twig")
     * @AclAncestor("orob2b_website_view")
     *
     * @param Product $product
     * @param Website $website
     * @return array
     */
    public function websiteWidgetAction(Product $product, Website $website)
    {
        return [
            'product' => $product,
            'website' => $website,
        ];
    }
}

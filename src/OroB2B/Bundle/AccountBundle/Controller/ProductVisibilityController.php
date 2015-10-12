<?php

namespace OroB2B\Bundle\AccountBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductVisibilityController
{
    /**
     * @Route("/edit/{id}", name="orob2b_account_product_visibility_edit", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_product_visibility_edit)
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
     * @Route("/website/{id}", name="orob2b_account_product_visibility_website_edit", requirements={"id"="\d+"})
     * @Template("OroB2BAccountBundle:ProductVisibility/widget:website.html.twig")
     * @AclAncestor("orob2b_website_view")
     *
     * @param Website $website
     * @return array
     */
    public function websiteAction(Website $website)
    {
        return [
            'entity' => $website,
        ];
    }
}

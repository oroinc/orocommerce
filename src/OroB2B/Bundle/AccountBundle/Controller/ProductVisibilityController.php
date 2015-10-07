<?php

namespace OroB2B\Bundle\AccountBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductVisibilityController
{
    /**
     * @Route("/edit/{id}", name="orob2b_account_product_visibility_edit", requirements={"id"="\d+"})
     * @Template
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
}

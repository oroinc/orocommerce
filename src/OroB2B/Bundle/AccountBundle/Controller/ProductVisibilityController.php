<?php

namespace OroB2B\Bundle\AccountBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductVisibilityController
{
    /**
     * @Route("/edit/{id}", name="orob2b_account_product_visibility_edit", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_product_visibility_edit",
     *      type="entity",
     *      class="OroB2BProductBundle:Product",
     *      permission="EDIT"
     * )
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

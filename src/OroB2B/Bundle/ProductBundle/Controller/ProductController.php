<?php

namespace OroB2B\Bundle\ProductBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_product_view", requirements={"id"="\d+"})
     * @Acl(
     *      id="orob2b_product_view",
     *      type="entity",
     *      class="OroB2BProductBundle:Product",
     *      permission="VIEW"
     * )
     *
     * @param Product $product
     * @return Response
     */
    public function viewAction(Product $product)
    {
        // TODO: Implement view action in scope of https://magecore.atlassian.net/browse/BB-250
        return new Response($product->getSku());
    }

    /**
     * @Route("/", name="orob2b_product_index")
     * @Template
     * @AclAncestor("orob2b_product_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_product.product.class')
        ];
    }
}

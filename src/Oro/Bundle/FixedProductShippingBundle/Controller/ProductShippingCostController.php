<?php

namespace Oro\Bundle\FixedProductShippingBundle\Controller;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Provides action for the product shipping cost widget
 */
class ProductShippingCostController extends AbstractController
{
    #[Route(
        path: '/widget/shipping_cost_update/{unit}/{precision}',
        name: 'oro_fixed_product_shipping_widget_shipping_cost_update',
        requirements: ['unit' => '[\w\s\:]+', 'precision' => '\d+']
    )]
    #[Template('@OroFixedProductShipping/Product/widget/shipping_cost_update.html.twig')]
    #[AclAncestor('oro_product_update')]
    public function widgetShippingUpdateAction(ProductUnit $unit, int $precision): array
    {
        $product = new Product();

        $productUnit = new ProductUnitPrecision();
        $productUnit->setProduct($product)
            ->setUnit($unit)
            ->setPrecision($precision);

        $product->setPrimaryUnitPrecision($productUnit);

        $form = $this->createForm(ProductType::class, $product);

        return ['form' => $form->createView()];
    }
}

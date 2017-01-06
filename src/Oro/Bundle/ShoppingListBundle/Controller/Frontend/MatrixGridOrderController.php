<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\ShoppingListBundle\Form\Type\MatrixCollectionType;

class MatrixGridOrderController extends Controller
{
    /**
     * @Route("/{productId}", name="oro_shopping_list_frontend_matrix_grid_order")
     * @ParamConverter("product", options={"id" = "productId"})
     * @Template("OroShoppingListBundle:MatrixGridOrder:order.html.twig")
     * @Acl(
     *      id="oro_shopping_list_frontend_view",
     *      type="entity",
     *      class="OroShoppingListBundle:ShoppingList",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     *
     * @param Product $product
     * @return array
     */
    public function orderAction(Product $product)
    {
        $variantAbilityProvider = $this->get('oro_product.provider.product_variant_availability_provider');
        $matrixGridOrderManager = $this->get('oro_shopping_list.provider.matrix_grid_order_manager');
        $formatter = $this->get('oro_locale.formatter.number');

        $variants = $variantAbilityProvider->getSimpleProductsByVariantFields($product);
        $variantFields = $matrixGridOrderManager->getVariantFields($product);
        $collection = $matrixGridOrderManager->createMatrixCollection($product, $variantFields);
        $totalQuantities = $matrixGridOrderManager->calculateTotalQuantities($collection);
        $totalPrice = $matrixGridOrderManager->calculateTotalPrice($collection);

        $form = $this->createForm(MatrixCollectionType::class, $collection);

        //TODO: handle form

        return [
            'variantFields' => $variantFields,
            'variants' => $variants,
            'totalQuantities' => $totalQuantities,
            'totalPrice' => $formatter->formatCurrency($totalPrice->getValue(), $totalPrice->getCurrency()),
            'form' => $form->createView(),
        ];
    }
}

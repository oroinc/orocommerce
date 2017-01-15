<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

class MatrixGridOrderController extends Controller
{
    /**
     * @Route("/{productId}", name="oro_shopping_list_frontend_matrix_grid_order")
     * @ParamConverter("product", options={"id" = "productId"})
     * @Layout()
     * @Acl(
     *      id="oro_shopping_list_frontend_view",
     *      type="entity",
     *      class="OroShoppingListBundle:ShoppingList",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     *
     * @param Request $request
     * @param Product $product
     * @return array
     */
    public function orderAction(Request $request, Product $product)
    {
        $matrixOrderFormProvider = $this->get('oro_shopping_list.layout.data_provider.matrix_order_form');
        $matrixGridOrderManager = $this->get('oro_shopping_list.provider.matrix_grid_order_manager');

        $shoppingListManager = $this->get('oro_shopping_list.shopping_list.manager');
        $shoppingList = $shoppingListManager->getForCurrentUser($request->get('shoppingListId'));

        $form = $matrixOrderFormProvider->getMatrixOrderForm($product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $lineItems = $matrixGridOrderManager->convertMatrixIntoLineItems($form->getData(), $shoppingList);

            foreach ($lineItems as $lineItem) {
                $shoppingListManager->addLineItem($lineItem, $shoppingList, true, true);
            }
        }

        return ['data' => ['product' => $product, 'form_action' => $request->getUri()]];
    }
}

<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixGridOrderFormProvider;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\MatrixGridOrderManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Submits matrix form for configurable products; also displays popup matrix form
 */
class MatrixGridOrderController extends AbstractLineItemController
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
     * @return array | JsonResponse
     */
    public function orderAction(Request $request, Product $product)
    {
        $matrixOrderFormProvider = $this->getMatrixOrderFormProvider();
        $matrixGridOrderManager = $this->getMatrixGridOrderManager();
        $currentShoppingListManager = $this->getCurrentShoppingListManager();

        $shoppingListId = $request->get('shoppingListId');
        $shoppingList = $shoppingListId
            ? $currentShoppingListManager->getForCurrentUser($shoppingListId)
            : $currentShoppingListManager->getCurrent();

        $form = $matrixOrderFormProvider->getMatrixOrderForm($product, $shoppingList);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $lineItems = $matrixGridOrderManager->convertMatrixIntoLineItems(
                $form->getData(),
                $product,
                $request->request->get('matrix_collection', [])
            );

            $shoppingList = $shoppingList ?? $currentShoppingListManager->getForCurrentUser($shoppingListId);
            $matrixGridOrderManager->addEmptyMatrixIfAllowed($shoppingList, $product, $lineItems);

            $shoppingListManager = $this->getShoppingListManager();
            foreach ($lineItems as $lineItem) {
                $shoppingListManager->updateLineItem($lineItem, $shoppingList);
            }

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(
                    $this->getSuccessResponse(
                        $shoppingList,
                        $product,
                        'oro.shoppinglist.flash.update_success'
                    )
                );
            }
        }

        return ['data' => [
            'product' => $product,
            'shoppingList' => $shoppingList,
            'hasLineItems' => $form->getData()->hasLineItems(),
        ]];
    }

    /**
     * @see \Oro\Bundle\ShoppingListBundle\Controller\Frontend\AjaxLineItemController::getSuccessResponse
     * @param ShoppingList $shoppingList
     * @param Product $product
     * @param string $message
     * @return array
     */
    protected function getSuccessResponse(ShoppingList $shoppingList, Product $product, $message)
    {
        $productShoppingLists = $this->get('oro_shopping_list.data_provider.product_shopping_lists')
            ->getProductUnitsQuantity($product);

        return [
            'successful' => true,
            'message' => $this->getSuccessMessage($shoppingList, $message),
            'product' => [
                'id' => $product->getId(),
                'shopping_lists' => $productShoppingLists
            ],
            'shoppingList' => [
                'id' => $shoppingList->getId(),
                'label' => $shoppingList->getLabel()
            ]
        ];
    }

    /**
     * @return MatrixGridOrderFormProvider
     */
    private function getMatrixOrderFormProvider()
    {
        return $this->get('oro_shopping_list.layout.data_provider.matrix_order_form');
    }

    /**
     * @return MatrixGridOrderManager
     */
    private function getMatrixGridOrderManager()
    {
        return $this->get('oro_shopping_list.provider.matrix_grid_order_manager');
    }

    /**
     * @return ShoppingListManager
     */
    private function getShoppingListManager()
    {
        return $this->get('oro_shopping_list.manager.shopping_list');
    }

    /**
     * @return CurrentShoppingListManager
     */
    private function getCurrentShoppingListManager()
    {
        return $this->get('oro_shopping_list.manager.current_shopping_list');
    }
}

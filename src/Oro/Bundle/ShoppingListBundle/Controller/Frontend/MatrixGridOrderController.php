<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Handler\MatrixGridOrderFormHandler;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixGridOrderFormProvider;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\MatrixGridOrderManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

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
        $currentShoppingListManager = $this->get(CurrentShoppingListManager::class);

        $shoppingListId = $request->get('shoppingListId');
        $shoppingList = $currentShoppingListManager->getForCurrentUser($shoppingListId);

        $form = $this->get(MatrixGridOrderFormProvider::class)->getMatrixOrderForm($product, $shoppingList);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $matrixGridOrderManager = $this->get(MatrixGridOrderManager::class);
            $lineItems = $matrixGridOrderManager->convertMatrixIntoLineItems(
                $form->getData(),
                $product,
                $request->request->get('matrix_collection', [])
            );

            $shoppingList = $shoppingList ?? $currentShoppingListManager->getForCurrentUser($shoppingListId, true);

            $shoppingListManager = $this->get(ShoppingListManager::class);
            foreach ($lineItems as $lineItem) {
                $shoppingListManager->updateLineItem($lineItem, $shoppingList);
            }

            $matrixGridOrderManager->addEmptyMatrixIfAllowed($shoppingList, $product, $lineItems);

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
     * @Route("/{shoppingListId}/{productId}/{unitCode}", name="oro_shopping_list_frontend_matrix_grid_update")
     * @ParamConverter("shoppingList", options={"id" = "shoppingListId"})
     * @ParamConverter("product", options={"id" = "productId"})
     * @ParamConverter("unit", options={"id" = "unitCode"})
     * @Layout()
     * @AclAncestor("oro_shopping_list_frontend_update")
     *
     * @param ShoppingList $shoppingList
     * @param Product $product
     * @param ProductUnit $unit
     * @param Request $request
     * @return array|JsonResponse
     */
    public function updateAction(ShoppingList $shoppingList, Product $product, ProductUnit $unit, Request $request)
    {
        $form = $this->get(MatrixGridOrderFormProvider::class)
            ->getMatrixOrderByUnitForm($product, $unit, $shoppingList);

        $result = $this->get(MatrixGridOrderFormHandler::class)
            ->process($form->getData(), $form, $request);

        if ($result) {
            return new JsonResponse(
                $this->getSuccessResponse($shoppingList, $product, 'oro.shoppinglist.flash.update_success')
            );
        }

        return [
            'data' => [
                'product' => $product,
                'productUnit' => $unit,
                'shoppingList' => $shoppingList,
                'hasLineItems' => $form->getData()->hasLineItems(),
            ]
        ];
    }

    /**
     * @see \Oro\Bundle\ShoppingListBundle\Controller\Frontend\AjaxLineItemController::getSuccessResponse
     * @param ShoppingList $shoppingList
     * @param Product $product
     * @param string $message
     * @return array
     */
    protected function getSuccessResponse(ShoppingList $shoppingList, Product $product, string $message): array
    {
        $productShoppingLists = $this->get(ProductShoppingListsDataProvider::class)
            ->getProductUnitsQuantity($product->getId());

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
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            CurrentShoppingListManager::class,
            ShoppingListManager::class,
            ProductShoppingListsDataProvider::class,
            MatrixGridOrderManager::class,
            MatrixGridOrderFormProvider::class,
            MatrixGridOrderFormHandler::class,
        ]);
    }
}

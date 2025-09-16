<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Oro\Bundle\LayoutBundle\Attribute\Layout;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Handler\MatrixGridOrderFormHandler;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixGridOrderFormProvider;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\MatrixGridOrderManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Submits matrix form for configurable products; also displays popup matrix form
 */
class MatrixGridOrderController extends AbstractLineItemController
{
    #[Route(path: '/{productId}', name: 'oro_shopping_list_frontend_matrix_grid_order')]
    #[Layout]
    #[Acl(
        id: 'oro_shopping_list_frontend_view',
        type: 'entity',
        class: ShoppingList::class,
        permission: 'VIEW',
        groupName: 'commerce'
    )]
    public function orderAction(
        Request $request,
        #[MapEntity(id: 'productId')]
        Product $product
    ): Response|array {
        $currentShoppingListManager = $this->container->get(CurrentShoppingListManager::class);

        $shoppingListId = $request->get('shoppingListId');
        $shoppingListId = $shoppingListId ? (int)$shoppingListId : null;
        $shoppingList = $currentShoppingListManager->getForCurrentUser($shoppingListId);

        $form = $this->container->get(MatrixGridOrderFormProvider::class)->getMatrixOrderForm($product, $shoppingList);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $matrixGridOrderManager = $this->container->get(MatrixGridOrderManager::class);
            $lineItems = $matrixGridOrderManager->convertMatrixIntoLineItems(
                $form->getData(),
                $product,
                $request->request->all('matrix_collection')
            );

            $shoppingList = $shoppingList ?? $currentShoppingListManager->getForCurrentUser($shoppingListId, true);

            $shoppingListManager = $this->container->get(ShoppingListManager::class);
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

    #[Route(path: '/{shoppingListId}/{productId}/{unitCode}', name: 'oro_shopping_list_frontend_matrix_grid_update')]
    #[Layout]
    #[AclAncestor('oro_shopping_list_frontend_update')]
    public function updateAction(
        #[MapEntity(id: 'shoppingListId')]
        ShoppingList $shoppingList,
        #[MapEntity(id: 'productId')]
        Product $product,
        #[MapEntity(id: 'unitCode')]
        ProductUnit $unit,
        Request $request
    ): Response|array {
        $form = $this->container->get(MatrixGridOrderFormProvider::class)
            ->getMatrixOrderByUnitForm($product, $unit, $shoppingList);

        $result = $this->container->get(MatrixGridOrderFormHandler::class)
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
     * @see AjaxLineItemController::getSuccessResponse
     */
    #[\Override]
    protected function getSuccessResponse(ShoppingList $shoppingList, Product $product, string $message): array
    {
        $productShoppingLists = $this->container->get(ProductShoppingListsDataProvider::class)
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

    #[\Override]
    public static function getSubscribedServices(): array
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

<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Controller\GridController;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionParametersParser;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponseInterface;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\LayoutBundle\Attribute\Layout;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Handler\LineItemHandler;
use Oro\Bundle\ShoppingListBundle\Form\Type\ShoppingListType;
use Oro\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemBatchUpdateHandler;
use Oro\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Model\LineItemModel;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller that manages products and line items for a shopping list via AJAX requests.
 */
#[CsrfProtection()]
class AjaxLineItemController extends AbstractLineItemController
{
    #[Route(
        path: '/add-product-from-view/{productId}',
        name: 'oro_shopping_list_frontend_add_product',
        requirements: ['productId' => '\d+'],
        methods: ['POST']
    )]
    #[AclAncestor('oro_product_frontend_view')]
    public function addProductFromViewAction(
        Request $request,
        #[MapEntity(id: 'productId')]
        Product $product
    ): JsonResponse {
        $currentShoppingListManager = $this->container->get(CurrentShoppingListManager::class);
        $shoppingListId = $request->get('shoppingListId');
        $shoppingListId = $shoppingListId ? (int)$shoppingListId : null;
        $shoppingList = $currentShoppingListManager->getForCurrentUser($shoppingListId, true);

        if (!$shoppingList) {
            throw $this->createNotFoundException();
        }

        if (!$this->container->get(AuthorizationCheckerInterface::class)->isGranted('EDIT', $shoppingList)) {
            throw $this->createAccessDeniedException();
        }

        $parentProduct = $this->getParentProduct($request);

        $lineItem = (new LineItem())
            ->setProduct($product)
            ->setShoppingList($shoppingList)
            ->setCustomerUser($shoppingList->getCustomerUser())
            ->setOrganization($shoppingList->getOrganization());

        if ($parentProduct && $parentProduct->isConfigurable()) {
            $lineItem->setParentProduct($parentProduct);
        }

        $form = $this->createForm(FrontendLineItemType::class, $lineItem);

        $handler = new LineItemHandler(
            $form,
            $request,
            $this->container->get(ManagerRegistry::class),
            $this->container->get(ShoppingListManager::class),
            $currentShoppingListManager,
            $this->container->get(ValidatorInterface::class)
        );
        $isFormHandled = $handler->process($lineItem);

        if (!$isFormHandled) {
            return new JsonResponse(['successful' => false, 'message' => (string)$form->getErrors(true, false)]);
        }

        return new JsonResponse(
            $this->getSuccessResponse($shoppingList, $product, 'oro.shoppinglist.product.added.label')
        );
    }

    #[Route(
        path: '/remove-line-item/{lineItemId}',
        name: 'oro_shopping_list_frontend_remove_line_item',
        requirements: ['lineItemId' => '\d+'],
        methods: ['DELETE']
    )]
    #[AclAncestor('oro_shopping_list_frontend_update')]
    public function removeLineItemAction(
        #[MapEntity(id: 'lineItemId')]
        LineItem $lineItem
    ): JsonResponse {
        if (!$this->isGranted('DELETE', $lineItem)) {
            throw $this->createAccessDeniedException();
        }

        $shoppingListManager = $this->container->get(ShoppingListManager::class);
        $isRemoved = $shoppingListManager->removeLineItem($lineItem);
        if ($isRemoved > 0) {
            $result = $this->getSuccessResponse(
                $lineItem->getShoppingList(),
                $lineItem->getProduct(),
                'oro.frontend.shoppinglist.lineitem.product.removed.label'
            );
        } else {
            $result = [
                'successful' => false,
                'message' => $this->container->get(TranslatorInterface::class)
                    ->trans('oro.frontend.shoppinglist.lineitem.product.cant_remove.label')
            ];
        }

        return new JsonResponse($result);
    }

    #[Route(
        path: '/remove-product-from-view/{productId}',
        name: 'oro_shopping_list_frontend_remove_product',
        requirements: ['productId' => '\d+'],
        methods: ['DELETE']
    )]
    #[AclAncestor('oro_shopping_list_frontend_update')]
    public function removeProductFromViewAction(
        Request $request,
        #[MapEntity(id: 'productId')]
        Product $product
    ): JsonResponse {
        $shoppingListManager = $this->container->get(ShoppingListManager::class);

        $shoppingListId = $request->get('shoppingListId');
        $shoppingListId = $shoppingListId ? (int)$shoppingListId : null;
        $shoppingList = $this->container->get(CurrentShoppingListManager::class)->getForCurrentUser($shoppingListId);

        $result = [
            'successful' => false,
            'message' => $this->container->get(TranslatorInterface::class)
                ->trans('oro.frontend.shoppinglist.lineitem.product.cant_remove.label')
        ];

        if ($shoppingList) {
            $count = $shoppingListManager->removeProduct($shoppingList, $product);
            if ($count) {
                $result = $this->getSuccessResponse(
                    $shoppingList,
                    $product,
                    'oro.frontend.shoppinglist.lineitem.product.removed.label'
                );
            }
        }

        return new JsonResponse($result);
    }

    #[Route(
        path: '/{gridName}/massAction/{actionName}',
        name: 'oro_shopping_list_add_products_massaction',
        methods: ['POST']
    )]
    #[AclAncestor('oro_shopping_list_frontend_update')]
    public function addProductsMassAction(Request $request, string $gridName, string $actionName): JsonResponse
    {
        $shoppingList = $this->container->get(ShoppingListLineItemHandler::class)
            ->getShoppingList($request->query->get('shoppingList'));

        $parameters = $this->container->get(MassActionParametersParser::class)->parse($request);
        $requestData = array_merge($request->query->all(), $request->request->all());

        $response = $this->container->get(MassActionDispatcher::class)->dispatch(
            $gridName,
            $actionName,
            $parameters,
            array_merge($requestData, ['shoppingList' => $shoppingList])
        );

        $data = [
            'successful' => $response->isSuccessful(),
            'message' => $response->getMessage()
        ];

        return new JsonResponse(array_merge($data, $response->getOptions()));
    }

    #[Route(
        path: '/{gridName}/massAction/{actionName}/create',
        name: 'oro_shopping_list_add_products_to_new_massaction'
    )]
    #[Layout]
    #[AclAncestor('oro_shopping_list_frontend_update')]
    public function addProductsToNewMassAction(Request $request, string $gridName, string $actionName): array
    {
        $shoppingList = $this->container->get(ShoppingListManager::class)->create();

        $form = $this->createForm(ShoppingListType::class, $shoppingList);
        $form->handleRequest($request);

        $response = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $parameters = $this->container->get(MassActionParametersParser::class)->parse($request);
            $requestData = array_merge($request->query->all(), $request->request->all());

            /** @var MassActionResponseInterface $result */
            $result = $this->container->get(MassActionDispatcher::class)
                ->dispatch(
                    $gridName,
                    $actionName,
                    $parameters,
                    array_merge($requestData, ['shoppingList' => $shoppingList])
                );

            $this->container->get(CurrentShoppingListManager::class)
                ->setCurrent($this->getUser(), $shoppingList);

            $response['messages']['data'][] = $result->getMessage();
            $response['savedId'] = $shoppingList->getId();
        }

        $defaultResponse = [
            'form' => $form->createView(),
            'savedId' => null,
            'messages' => ['data' => []],
            'shoppingList' => $shoppingList,
            'createOnly' => $request->get('createOnly'),
            'routeParameters' => [
                'data' => array_merge($request->query->all(), [
                    'gridName' => $gridName,
                    'actionName' => $actionName,
                ])
            ]
        ];

        return ['data' => array_merge($defaultResponse, $response)];
    }

    #[Route(
        path: '/batch-update/{id}',
        name: 'oro_shopping_list_frontend_line_item_batch_update',
        requirements: ['id' => '\d+'],
        methods: ['PUT']
    )]
    #[AclAncestor('oro_shopping_list_frontend_update')]
    public function batchUpdateAction(Request $request, ShoppingList $shoppingList): Response
    {
        $data = \json_decode($request->getContent(), true);
        if (!isset($data['data']) || !is_array($data['data'])) {
            return $this->json(['message' => 'The request data should not be empty'], Response::HTTP_BAD_REQUEST);
        }

        $handler = $this->container->get(ShoppingListLineItemBatchUpdateHandler::class);

        $errors = $handler->process($this->getLineItemModels($data['data']), $shoppingList);
        if ($errors) {
            return $this->json(['message' => implode(', ', $errors)], Response::HTTP_BAD_REQUEST);
        }

        if (!empty($data['fetchData']) && is_array($data['fetchData'])) {
            foreach ($data['fetchData'] as $key => $value) {
                $request->query->set($key, $value);
            }
        }

        if (!empty($data['gridName']) && is_string($data['gridName'])) {
            $gridName = $data['gridName'];
        } else {
            $gridName = 'frontend-customer-user-shopping-list-edit-grid';
        }

        return $this->forward(GridController::class . '::getAction', ['gridName' => $gridName], $request->query->all());
    }

    private function getLineItemModels(array $rawLineItems): array
    {
        return array_filter(
            array_map(
                static function (array $item) {
                    $quantity = (float)$item['quantity'];

                    return $quantity > 0
                        ? new LineItemModel((int)$item['id'], $quantity, (string)$item['unitCode'])
                        : null;
                },
                $rawLineItems
            )
        );
    }

    protected function getParentProduct(Request $request): ?Product
    {
        $parentProductId = $request->get('parentProductId');

        return $parentProductId
            ? $this->container->get(ManagerRegistry::class)->getRepository(Product::class)->find($parentProductId)
            : null;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                MassActionDispatcher::class,
                CurrentShoppingListManager::class,
                ShoppingListManager::class,
                ShoppingListLineItemHandler::class,
                MassActionParametersParser::class,
                ValidatorInterface::class,
                AuthorizationCheckerInterface::class,
                UpdateHandlerFacade::class,
                ShoppingListLineItemBatchUpdateHandler::class,
                ManagerRegistry::class,
            ]
        );
    }
}

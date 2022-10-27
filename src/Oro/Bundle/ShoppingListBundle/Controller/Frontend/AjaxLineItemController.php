<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Oro\Bundle\DataGridBundle\Controller\GridController;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionParametersParser;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponseInterface;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Oro\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Handler\LineItemHandler;
use Oro\Bundle\ShoppingListBundle\Form\Type\ShoppingListType;
use Oro\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemBatchUpdateHandler;
use Oro\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Model\LineItemModel;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller that manages products and line items for a shopping list via AJAX requests.
 * @CsrfProtection()
 */
class AjaxLineItemController extends AbstractLineItemController
{
    /**
     * Add Product to Shopping List (product view form)
     *
     * @Route(
     *      "/add-product-from-view/{productId}",
     *      name="oro_shopping_list_frontend_add_product",
     *      requirements={"productId"="\d+"},
     *      methods={"POST"}
     * )
     * @AclAncestor("oro_product_frontend_view")
     * @ParamConverter("product", class="OroProductBundle:Product", options={"id" = "productId"})
     *
     * @param Request $request
     * @param Product $product
     *
     * @return JsonResponse
     */
    public function addProductFromViewAction(Request $request, Product $product)
    {
        $currentShoppingListManager = $this->get(CurrentShoppingListManager::class);
        $shoppingList = $currentShoppingListManager->getForCurrentUser($request->get('shoppingListId'), true);

        if (!$this->get(AuthorizationCheckerInterface::class)->isGranted('EDIT', $shoppingList)) {
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
            $this->getDoctrine(),
            $this->get(ShoppingListManager::class),
            $currentShoppingListManager,
            $this->get(ValidatorInterface::class)
        );
        $isFormHandled = $handler->process($lineItem);

        if (!$isFormHandled) {
            return new JsonResponse(['successful' => false, 'message' => (string)$form->getErrors(true, false)]);
        }

        return new JsonResponse(
            $this->getSuccessResponse($shoppingList, $product, 'oro.shoppinglist.product.added.label')
        );
    }

    /**
     * Remove Line item from Shopping List
     *
     * @Route(
     *      "/remove-line-item/{lineItemId}",
     *      name="oro_shopping_list_frontend_remove_line_item",
     *      requirements={"lineItemId"="\d+"},
     *      methods={"DELETE"}
     * )
     * @AclAncestor("oro_shopping_list_frontend_update")
     * @ParamConverter("lineItem", class="OroShoppingListBundle:LineItem", options={"id" = "lineItemId"})
     *
     * @param LineItem $lineItem
     *
     * @return JsonResponse
     */
    public function removeLineItemAction(LineItem $lineItem)
    {
        if (!$this->isGranted('DELETE', $lineItem)) {
            throw $this->createAccessDeniedException();
        }

        $shoppingListManager = $this->get(ShoppingListManager::class);
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
                'message' => $this->get(TranslatorInterface::class)
                    ->trans('oro.frontend.shoppinglist.lineitem.product.cant_remove.label')
            ];
        }

        return new JsonResponse($result);
    }

    /**
     * Remove Product from Shopping List (product view form)
     *
     * @Route(
     *      "/remove-product-from-view/{productId}",
     *      name="oro_shopping_list_frontend_remove_product",
     *      requirements={"productId"="\d+"},
     *      methods={"DELETE"}
     * )
     * @AclAncestor("oro_shopping_list_frontend_update")
     * @ParamConverter("product", class="OroProductBundle:Product", options={"id" = "productId"})
     *
     * @param Request $request
     * @param Product $product
     *
     * @return JsonResponse
     */
    public function removeProductFromViewAction(Request $request, Product $product)
    {
        $shoppingListManager = $this->get(ShoppingListManager::class);

        $shoppingList = $this->get(CurrentShoppingListManager::class)
            ->getForCurrentUser($request->get('shoppingListId'));

        $result = [
            'successful' => false,
            'message' => $this->get(TranslatorInterface::class)
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

    /**
     * @Route("/{gridName}/massAction/{actionName}", name="oro_shopping_list_add_products_massaction", methods={"POST"})
     * @AclAncestor("oro_shopping_list_frontend_update")
     *
     * @param Request $request
     * @param string $gridName
     * @param string $actionName
     *
     * @return JsonResponse
     */
    public function addProductsMassAction(Request $request, $gridName, $actionName)
    {
        $shoppingList = $this->get(ShoppingListLineItemHandler::class)
            ->getShoppingList($request->query->get('shoppingList'));

        $parameters = $this->get(MassActionParametersParser::class)->parse($request);
        $requestData = array_merge($request->query->all(), $request->request->all());

        $response = $this->get(MassActionDispatcher::class)->dispatch(
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

    /**
     * @Route("/{gridName}/massAction/{actionName}/create", name="oro_shopping_list_add_products_to_new_massaction")
     * @Layout
     * @AclAncestor("oro_shopping_list_frontend_update")
     *
     * @param Request $request
     * @param string $gridName
     * @param string $actionName
     *
     * @return array
     */
    public function addProductsToNewMassAction(Request $request, $gridName, $actionName)
    {
        $shoppingList = $this->get(ShoppingListManager::class)->create();

        $form = $this->createForm(ShoppingListType::class, $shoppingList);
        $form->handleRequest($request);

        $response = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $parameters = $this->get(MassActionParametersParser::class)->parse($request);
            $requestData = array_merge($request->query->all(), $request->request->all());

            /** @var MassActionResponseInterface $result */
            $result = $this->get(MassActionDispatcher::class)
                ->dispatch(
                    $gridName,
                    $actionName,
                    $parameters,
                    array_merge($requestData, ['shoppingList' => $shoppingList])
                );

            $this->get(CurrentShoppingListManager::class)
                ->setCurrent($this->getUser(), $shoppingList);

            $response['messages']['data'][] = $result->getMessage();
            $response['savedId'] = $shoppingList->getId();
        }

        $defaultResponse = [
            'form' => $form->createView(),
            'savedId' => null,
            'messages' => ['data'=>[]],
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

    /**
     * Update shopping list line items in batch
     *
     * @Route(
     *      "/batch-update/{id}",
     *      name="oro_shopping_list_frontend_line_item_batch_update",
     *      requirements={"id"="\d+"},
     *      methods={"PUT"}
     * )
     * @AclAncestor("oro_shopping_list_frontend_update")
     *
     * @param Request $request
     * @param ShoppingList $shoppingList
     *
     * @return JsonResponse
     */
    public function batchUpdateAction(Request $request, ShoppingList $shoppingList): Response
    {
        $data = \json_decode($request->getContent(), true);
        if (!isset($data['data']) || !is_array($data['data'])) {
            return $this->json(['message' => 'The request data should not be empty'], Response::HTTP_BAD_REQUEST);
        }

        $handler = $this->get(ShoppingListLineItemBatchUpdateHandler::class);

        $errors = $handler->process($this->getLineItemModels($data['data']), $shoppingList);
        if ($errors) {
            return $this->json(['message' => implode(', ', $errors)], Response::HTTP_BAD_REQUEST);
        }

        foreach ($data['fetchData'] ?? [] as $key => $value) {
            $request->query->set($key, $value);
        }

        return $this->forward(
            GridController::class . '::getAction',
            ['gridName' => 'frontend-customer-user-shopping-list-edit-grid'],
            $request->query->all()
        );
    }

    private function getLineItemModels(array $rawLineItems): array
    {
        return array_filter(
            array_map(
                static function (array $item) {
                    $quantity = (float)$item['quantity'];

                    return $quantity > 0 ?
                        new LineItemModel((int)$item['id'], $quantity, (string)$item['unitCode'])
                        : null;
                },
                $rawLineItems
            )
        );
    }

    /**
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

    protected function getParentProduct(Request $request): ?Product
    {
        $parentProductId = $request->get('parentProductId');

        return $parentProductId
            ? $this->getDoctrine()->getRepository(Product::class)->find($parentProductId)
            : null;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                MassActionDispatcher::class,
                ProductShoppingListsDataProvider::class,
                CurrentShoppingListManager::class,
                ShoppingListManager::class,
                ShoppingListLineItemHandler::class,
                MassActionParametersParser::class,
                ValidatorInterface::class,
                AuthorizationCheckerInterface::class,
                UpdateHandlerFacade::class,
                ShoppingListLineItemBatchUpdateHandler::class,
            ]
        );
    }
}

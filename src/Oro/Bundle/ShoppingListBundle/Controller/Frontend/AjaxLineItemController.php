<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponseInterface;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Handler\LineItemHandler;
use Oro\Bundle\ShoppingListBundle\Form\Type\ShoppingListType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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
     *      requirements={"productId"="\d+"}
     * )
     * @AclAncestor("oro_product_frontend_view")
     * @ParamConverter("product", class="OroProductBundle:Product", options={"id" = "productId"})
     * @Method("POST")
     *
     * @param Request $request
     * @param Product $product
     *
     * @return JsonResponse
     */
    public function addProductFromViewAction(Request $request, Product $product)
    {
        $validator = $this->get('validator');
        $currentShoppingListManager = $this->get('oro_shopping_list.manager.current_shopping_list');
        $shoppingList = $currentShoppingListManager->getForCurrentUser($request->get('shoppingListId'));

        if (!$this->get('security.authorization_checker')->isGranted('EDIT', $shoppingList)) {
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
            $this->get('oro_shopping_list.manager.shopping_list'),
            $currentShoppingListManager,
            $validator
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
     *      requirements={"lineItemId"="\d+"}
     * )
     * @AclAncestor("oro_shopping_list_frontend_update")
     * @ParamConverter("lineItem", class="OroShoppingListBundle:LineItem", options={"id" = "lineItemId"})
     * @Method("DELETE")
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

        $shoppingListManager = $this->get('oro_shopping_list.manager.shopping_list');
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
                'message' => $this->get('translator')
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
     *      requirements={"productId"="\d+"}
     * )
     * @AclAncestor("oro_shopping_list_frontend_update")
     * @ParamConverter("product", class="OroProductBundle:Product", options={"id" = "productId"})
     * @Method("POST")
     *
     * @param Request $request
     * @param Product $product
     *
     * @return JsonResponse
     */
    public function removeProductFromViewAction(Request $request, Product $product)
    {
        $shoppingListManager = $this->get('oro_shopping_list.manager.shopping_list');

        $shoppingList = $this->get('oro_shopping_list.manager.current_shopping_list')
            ->getForCurrentUser($request->get('shoppingListId'));

        $result = [
            'successful' => false,
            'message' => $this->get('translator')
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
     * @Route("/{gridName}/massAction/{actionName}", name="oro_shopping_list_add_products_massaction")
     * @AclAncestor("oro_shopping_list_frontend_update")
     * @Method("POST")
     *
     * @param Request $request
     * @param string $gridName
     * @param string $actionName
     *
     * @return JsonResponse
     */
    public function addProductsMassAction(Request $request, $gridName, $actionName)
    {
        $shoppingList = $this->get('oro_shopping_list.handler.shopping_list_line_item')
            ->getShoppingList($request->query->get('shoppingList'));

        $parameters = $this->get('oro_datagrid.mass_action.parameters_parser')->parse($request);
        $requestData = array_merge($request->query->all(), $request->request->all());

        $response = $this->get('oro_datagrid.mass_action.dispatcher')->dispatch(
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
        $shoppingList = $this->get('oro_shopping_list.manager.shopping_list')->create();

        $form = $this->createForm(ShoppingListType::class, $shoppingList);
        $form->handleRequest($request);

        $response = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $parameters = $this->get('oro_datagrid.mass_action.parameters_parser')->parse($request);
            $requestData = array_merge($request->query->all(), $request->request->all());

            /** @var MassActionResponseInterface $result */
            $result = $this->get('oro_datagrid.mass_action.dispatcher')
                ->dispatch(
                    $gridName,
                    $actionName,
                    $parameters,
                    array_merge($requestData, ['shoppingList' => $shoppingList])
                );

            $this->get('oro_shopping_list.manager.current_shopping_list')
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
     * @param Request $request
     * @return null|Product
     */
    protected function getParentProduct(Request $request)
    {
        if ($parentProductId = $request->get('parentProductId')) {
            $doctrineHelper = $this->get('oro_entity.doctrine_helper');
            $productRepository = $doctrineHelper->getEntityRepositoryForClass(Product::class);
            return $productRepository->find($parentProductId);
        }

        return null;
    }
}

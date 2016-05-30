<?php

namespace OroB2B\Bundle\ShoppingListBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Form\Handler\LineItemHandler;
use OroB2B\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\ShoppingListType;

class AjaxLineItemController extends Controller
{
    /**
     * Add Product to Shopping List (product view form)
     *
     * @Route(
     *      "/add-product-from-view/{productId}",
     *      name="orob2b_shopping_list_frontend_add_product",
     *      requirements={"productId"="\d+"}
     * )
     * @Acl(
     *      id="orob2b_shopping_list_line_item_frontend_add",
     *      type="entity",
     *      class="OroB2BShoppingListBundle:LineItem",
     *      permission="CREATE",
     *      group_name="commerce"
     * )
     * @ParamConverter("product", class="OroB2BProductBundle:Product", options={"id" = "productId"})
     *
     * @param Request $request
     * @param Product $product
     *
     * @return JsonResponse
     */
    public function addProductFromViewAction(Request $request, Product $product)
    {
        $shoppingListManager = $this->get('orob2b_shopping_list.shopping_list.manager');
        $shoppingList = $shoppingListManager->getForCurrentUser($request->get('shoppingListId'));

        $lineItem = (new LineItem())
            ->setProduct($product)
            ->setShoppingList($shoppingList)
            ->setAccountUser($shoppingList->getAccountUser())
            ->setOrganization($shoppingList->getOrganization());

        $form = $this->createForm(FrontendLineItemType::NAME, $lineItem);

        $handler = new LineItemHandler(
            $form,
            $request,
            $this->getDoctrine(),
            $shoppingListManager,
            $this->get('orob2b_product.service.quantity_rounding')
        );
        $isFormHandled = $handler->process($lineItem);

        if (!$isFormHandled) {
            return new JsonResponse(['successful' => false, 'message' => (string)$form->getErrors(true, false)]);
        }

        return new JsonResponse(
            $this->getSuccessResponse($shoppingList, $product, 'orob2b.shoppinglist.product.added.label')
        );
    }

    /**
     * Remove Product from Shopping List (product view form)
     *
     * @Route(
     *      "/remove-product-from-view/{productId}",
     *      name="orob2b_shopping_list_frontend_remove_product",
     *      requirements={"productId"="\d+"}
     * )
     * @Acl(
     *      id="orob2b_shopping_list_line_item_frontend_remove",
     *      type="entity",
     *      class="OroB2BShoppingListBundle:LineItem",
     *      permission="DELETE",
     *      group_name="commerce"
     * )
     * @ParamConverter("product", class="OroB2BProductBundle:Product", options={"id" = "productId"})
     * @Method("POST")
     *
     * @param Request $request
     * @param Product $product
     *
     * @return JsonResponse
     */
    public function removeProductFromViewAction(Request $request, Product $product)
    {
        $shoppingListManager = $this->get('orob2b_shopping_list.shopping_list.manager');

        $shoppingList = $shoppingListManager->getForCurrentUser($request->get('shoppingListId'));

        $result = [
            'successful' => false,
            'message' => $this->get('translator')
                ->trans('orob2b.frontend.shoppinglist.lineitem.product.cant_remove.label')
        ];

        if ($shoppingList) {
            $count = $shoppingListManager->removeProduct($shoppingList, $product);

            if ($count) {
                $result = $this->getSuccessResponse(
                    $shoppingList,
                    $product,
                    'orob2b.frontend.shoppinglist.lineitem.product.removed.label'
                );
            }
        }

        return new JsonResponse($result);
    }

    /**
     * @Route("/{gridName}/massAction/{actionName}", name="orob2b_shopping_list_add_products_massaction")
     * @AclAncestor("orob2b_shopping_list_line_item_frontend_add")
     *
     * @param Request $request
     * @param string $gridName
     * @param string $actionName
     *
     * @return JsonResponse
     */
    public function addProductsMassAction(Request $request, $gridName, $actionName)
    {
        /** @var MassActionDispatcher $massActionDispatcher */
        $massActionDispatcher = $this->get('oro_datagrid.mass_action.dispatcher');

        $response = $massActionDispatcher->dispatchByRequest($gridName, $actionName, $request);

        $data = [
            'successful' => $response->isSuccessful(),
            'message' => $response->getMessage()
        ];

        return new JsonResponse(array_merge($data, $response->getOptions()));
    }

    /**
     * @Route("/{gridName}/massAction/{actionName}/create", name="orob2b_shopping_list_add_products_to_new_massaction")
     * @Template("OroB2BShoppingListBundle:ShoppingList/Frontend:update.html.twig")
     * @AclAncestor("orob2b_shopping_list_line_item_frontend_add")
     *
     * @param Request $request
     * @param string $gridName
     * @param string $actionName
     *
     * @return JsonResponse
     */
    public function addProductsToNewMassAction(Request $request, $gridName, $actionName)
    {
        $form = $this->createForm(ShoppingListType::NAME);
        $manager = $this->get('orob2b_shopping_list.shopping_list.manager');
        $response = $this->get('oro_form.model.update_handler')->handleUpdate($manager->create(), $form, [], [], null);

        if ($form->isValid()) {
            $manager->setCurrent($this->getUser(), $form->getData());

            $result = $this->get('oro_datagrid.mass_action.dispatcher')
                ->dispatchByRequest($gridName, $actionName, $request);

            $response['message'] = $result->getMessage();
        }

        return $response;
    }

    /**
     * @param ShoppingList $shoppingList
     * @param string $translationKey
     * @return string
     */
    protected function getSuccessMessage(ShoppingList $shoppingList, $translationKey)
    {
        $link = $this->get('router')->generate('orob2b_shopping_list_frontend_view', ['id' => $shoppingList->getId()]);

        $translator = $this->get('translator');

        return $translator->trans(
            $translationKey,
            ['%shoppinglist%' => sprintf('<a href="%s">%s</a>', $link, $shoppingList->getLabel())]
        );
    }

    /**
     * @param ShoppingList $shoppingList
     * @param Product $product
     * @param string $message
     * @return array
     */
    protected function getSuccessResponse(ShoppingList $shoppingList, Product $product, $message)
    {
        $productLineItems = [];
        foreach ($shoppingList->getLineItems() as $lineItem) {
            if ($lineItem->getProduct() === $product) {
                $productLineItems[$lineItem->getProductUnitCode()] = $lineItem->getQuantity();
            }
        }

        return [
            'successful' => true,
            'message' => $this->getSuccessMessage($shoppingList, $message),
            'product' => [
                'id' => $product->getId(),
                'lineItems' => $productLineItems
            ],
            'shoppingList' => [
                'id' => $shoppingList->getId(),
                'label' => $shoppingList->getLabel()
            ]
        ];
    }
}

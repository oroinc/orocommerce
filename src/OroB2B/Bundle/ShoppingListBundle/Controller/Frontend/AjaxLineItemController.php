<?php

namespace OroB2B\Bundle\ShoppingListBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Form\Handler\LineItemHandler;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\FrontendLineItemWidgetType;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\FrontendLineItemType;

class AjaxLineItemController extends Controller
{
    /**
     * Add Product to Shopping List (frontend grid action)
     *
     * @Route(
     *      "/add-product-from-grid/{productId}",
     *      name="orob2b_shopping_list_line_item_frontend_add_widget",
     *      requirements={"productId"="\d+"}
     * )
     * @Template("OroB2BShoppingListBundle:LineItem/Frontend/widget:add.html.twig")
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
     * @return array|RedirectResponse
     */
    public function addProductAction(Request $request, Product $product)
    {
        /** @var AccountUser $accountUser */
        $accountUser = $this->getUser();
        $lineItem = (new LineItem())
            ->setProduct($product)
            ->setAccountUser($accountUser)
            ->setOrganization($accountUser->getOrganization());

        $form = $this->createForm(FrontendLineItemWidgetType::NAME, $lineItem);

        $handler = new LineItemHandler(
            $form,
            $request,
            $this->getDoctrine(),
            $this->get('orob2b_shopping_list.shopping_list.manager'),
            $this->get('orob2b_product.service.quantity_rounding')
        );
        $result = $this->get('oro_form.model.update_handler')
            ->handleUpdate($lineItem, $form, [], [], null, $handler);

        if ($request->get('_wid')) {
            $result = $handler->updateSavedId($result);
            if ($lineItem->getShoppingList()) {
                $result['shoppingListId'] = $lineItem->getShoppingList()->getId();
            }
        }

        return $result;
    }

    /**
     * Add Product to Shopping List (product view form)
     *
     * @Route(
     *      "/add-product-from-view/{productId}",
     *      name="orob2b_shopping_list_frontend_add_product",
     *      requirements={"productId"="\d+"}
     * )
     * @AclAncestor("orob2b_shopping_list_line_item_frontend_add")
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

        $link = $this->get('router')->generate('orob2b_shopping_list_frontend_view', ['id' => $shoppingList->getId()]);
        $translator = $this->get('translator');
        $message = $translator->trans('orob2b.shoppinglist.product.added.label');
        $linkTitle = $translator->trans('orob2b.shoppinglist.actions.view');
        $message = sprintf("%s (<a href='%s'>%s</a>).", $message, $link, $linkTitle);

        return new JsonResponse(['successful' => true, 'message' => $message]);
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
}

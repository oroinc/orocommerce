<?php

namespace OroB2B\Bundle\ShoppingListBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Form\Handler\LineItemHandler;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\FrontendLineItemWidgetType;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\FrontendLineItemType;

class AjaxLineItemController extends Controller
{
    /**
     * Add Product to shopping list (create line item) form
     *
     * @Route(
     *      "/add-product/{productId}",
     *      name="orob2b_shopping_list_line_item_frontend_add_widget",
     *      requirements={"productId"="\d+"}
     * )
     * @Template("OroB2BShoppingListBundle:LineItem/Frontend/widget:add.html.twig")
     * @AclAncestor("orob2b_shoppinglist_add_product")
     * @ParamConverter("product", class="OroB2BProductBundle:Product", options={"id" = "productId"})
     *
     * @param Request $request
     * @param Product $product
     *
     * @return array|RedirectResponse
     */
    public function addProductAction(Request $request, Product $product)
    {
        $lineItem = new LineItem();
        $lineItem->setProduct($product);

        $form = $this->createForm(FrontendLineItemWidgetType::NAME, $lineItem);

        $handler = new LineItemHandler($form, $request, $this->getDoctrine());
        $result = $this->get('oro_form.model.update_handler')
            ->handleUpdate($lineItem, $form, null, null, null, $handler);

        if ($request->get('_wid')) {
            $result = $handler->updateSavedId($result);
        }

        return $result;
    }

    /**
     * Add Product to Shopping List
     *
     * @Route(
     *      "/{shoppingListId}/products/{productId}",
     *      name="orob2b_shopping_list_frontend_add_product",
     *      requirements={"shoppingListId"="\d+", "productId"="\d+"}
     * )
     * @AclAncestor("orob2b_shopping_list_frontend_create")
     * @ParamConverter("shoppingList", class="OroB2BShoppingListBundle:ShoppingList", options={"id" = "shoppingListId"})
     * @ParamConverter("product", class="OroB2BProductBundle:Product", options={"id" = "productId"})
     *
     * @param Request $request
     * @param ShoppingList $shoppingList
     * @param Product $product
     *
     * @return array|RedirectResponse
     */
    public function addProductFromViewAction(Request $request, ShoppingList $shoppingList, Product $product)
    {
        $lineItem = new LineItem();
        $lineItem->setProduct($product);
        $lineItem->setShoppingList($shoppingList);

        $form = $this->createForm(FrontendLineItemType::NAME, $lineItem);
        $handler = new LineItemHandler($form, $request, $this->getDoctrine());
        $saveRoute = function (LineItem $lineItem) {
            return [
                'route' => 'orob2b_product_frontend_product_view',
                'parameters' => ['id' => $lineItem->getProduct()->getId()]
            ];
        };

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $lineItem,
            $form,
            $saveRoute,
            $saveRoute,
            $this->get('translator')->trans('orob2b.shoppinglist.line_item_save.flash.success', [], 'jsmessages'),
            $handler
        );
    }
}

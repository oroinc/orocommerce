<?php

namespace OroB2B\Bundle\ShoppingListBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\LineItemType;
use OroB2B\Bundle\ShoppingListBundle\Form\Handler\LineItemHandler;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\AddProductType;

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
     * @param Product $product
     *
     * @return array|RedirectResponse
     */
    public function addProductAction(Product $product)
    {
        $lineItem = new LineItem();
        $lineItem->setProduct($product);

        $form = $this->createForm(AddProductType::NAME, $lineItem);
        $request = $this->getRequest();

        $handler = new LineItemHandler($form, $request, $this->getDoctrine());
        $result = $this->get('oro_form.model.update_handler')
            ->handleUpdate($lineItem, $form, null, null, null, $handler);

        if ($request->get('_wid')) {
            $result = $handler->updateSavedId($result);
        }

        return $result;
    }

    /**
     * Edit product form
     *
     * @Route("/update/{id}", name="orob2b_shopping_list_line_item_frontend_update_widget", requirements={"id"="\d+"})
     * @Template("OroB2BShoppingListBundle:LineItem:widget/update.html.twig")
     *
     * @param LineItem $lineItem
     *
     * @return array|RedirectResponse
     */
    public function updateAction(LineItem $lineItem)
    {
        $form = $this->createForm(LineItemType::NAME, $lineItem);

        return $this->get('oro_form.model.update_handler')
            ->handleUpdate($lineItem, $form, null, null, null);
    }
}

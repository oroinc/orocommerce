<?php
namespace OroB2B\Bundle\ShoppingListBundle\Controller\Frontend;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\AddProductType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\LineItemType;

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
     * @Acl(
     *      id="orob2b_shopping_list_line_item_frontend_add",
     *      type="entity",
     *      class="OroB2BShoppingListBundle:LineItem",
     *      permission="CREATE"
     * )
     * @ParamConverter("product", class="OroB2BProductBundle:Product", options={"id" = "productId"})
     *
     * @param Product $product
     *
     * @return array|RedirectResponse
     */
    public function addProductAction(Product $product)
    {
        return [
            'form' => $this->createForm(AddProductType::NAME)
        ];
    }
}

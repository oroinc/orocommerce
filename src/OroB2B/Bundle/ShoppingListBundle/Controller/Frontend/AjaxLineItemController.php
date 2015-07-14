<?php
namespace OroB2B\Bundle\ShoppingListBundle\Controller\Frontend;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\AddProductType;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\FrontendLineItemType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\JsonResponse;
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
            //'form' => $this->createForm(AddProductType::NAME)->createView()
        ];
    }

    /**
     * Add Product to existing shopping list (create line item) form
     *
     * @Route(
     *      "/shopping-lists/{shoppingListId}/products/{productId}",
     *      name="orob2b_shopping_list_frontend_add_product",
     *      requirements={"shoppingListId"="\d+", "productId"="\d+"}
     * )
     * @Acl(
     *      id="orob2b_shopping_list_line_item_frontend_add",
     *      type="entity",
     *      class="OroB2BShoppingListBundle:LineItem",
     *      permission="CREATE"
     * )
     * @ParamConverter("shoppingList", class="OroB2BShoppingListBundle:ShoppingList", options={"id" = "shoppingListId"})
     * @ParamConverter("product", class="OroB2BProductBundle:Product", options={"id" = "productId"})
     *
     * @param ShoppingList $shoppingList
     * @param Product $product
     *
     * @return array|RedirectResponse
     */
    public function addNewProductAction(ShoppingList $shoppingList, Product $product)
    {
//        $shoppingList = $this->createShoppingList($this->getUser());
        $lineItem = new LineItem();
        $lineItem->setProduct($product);
        $lineItem->setShoppingList($shoppingList);

        return $this->update($lineItem);
    }

    /**
     * @param LineItem $lineItem
     *
     * @return array|RedirectResponse
     */
    protected function update(LineItem $lineItem)
    {
        $form = $this->createForm(FrontendLineItemType::NAME, $lineItem);

        $isFormHandled = $this->get('orob2b_shopping_list.form.handler.frontend_line_item')->handle($form, $lineItem);

        if (!$isFormHandled) {
            return new JsonResponse(['successful' => false, 'message' => $form->getErrorsAsString()]);
        }

        $message = $this->get('translator')->trans('orob2b.shoppinglist.product_added.flash.success', [], 'jsmessages');

        return new JsonResponse(['successful' => true, 'message' => $message]);
    }

    /**
     * @param AccountUser $accountUser
     *
     * @return ShoppingList
     */
    protected function createShoppingList(AccountUser $accountUser)
    {
        $shoppingList = new ShoppingList();
        $shoppingList->setAccountUser($accountUser);
        $shoppingList->setAccount($accountUser->getCustomer());
        $shoppingList->setOwner($accountUser);
        $shoppingList->setOrganization($accountUser->getOrganization());
        $shoppingList->setCurrent(false);
        $shoppingList->setLabel('test1');

        $manager = $this->get('oro_entity.doctrine_helper')->getEntityManager($shoppingList);
        $manager->persist($shoppingList);
        $manager->flush();

        return $shoppingList;
    }
}

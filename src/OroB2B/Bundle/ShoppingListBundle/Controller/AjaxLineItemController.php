<?php

namespace OroB2B\Bundle\ShoppingListBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\LineItemType;
use OroB2B\Bundle\ShoppingListBundle\Form\Handler\LineItemHandler;

class AjaxLineItemController extends Controller
{
    /**
     * Edit product form
     *
     * @Route("/update/{id}", name="orob2b_shopping_list_line_item_update_widget", requirements={"id"="\d+"})
     * @Template("OroB2BShoppingListBundle:LineItem:widget/update.html.twig")
     * @Acl(
     *      id="orob2b_shopping_list_line_item_update",
     *      type="entity",
     *      class="OroB2BShoppingListBundle:LineItem",
     *      permission="EDIT"
     * )
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

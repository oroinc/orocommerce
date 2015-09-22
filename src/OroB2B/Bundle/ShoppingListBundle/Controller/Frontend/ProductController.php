<?php

namespace OroB2B\Bundle\ShoppingListBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\FrontendLineItemType;

class ProductController extends Controller
{
    /**
     * @Configuration\Route(
     *      "/add/{productId}",
     *      name="orob2b_shopping_list_product_shopping_list_form",
     *      requirements={"productId"="\d+"}
     * )
     * @AclAncestor("orob2b_shopping_list_line_item_frontend_add")
     * @Configuration\Template("OroB2BShoppingListBundle:Product/Frontend/widget:form.html.twig")
     *
     * @ParamConverter("product", class="OroB2BProductBundle:Product", options={"id" = "productId"})
     *
     * @param Product $product
     * @return Response
     */
    public function productAddToShoppingListFormAction(Product $product)
    {
        $accountUser = $this->getUser();
        if (!$accountUser instanceof AccountUser) {
            throw new AccessDeniedException();
        }

        $lineItem = (new LineItem())
            ->setProduct($product)
            ->setAccountUser($accountUser)
            ->setOrganization($accountUser->getOrganization());

        /** @var ShoppingListRepository $shoppingListRepository */
        $shoppingListRepository = $this->get('doctrine')->getRepository(
            $this->getParameter('orob2b_shopping_list.entity.shopping_list.class')
        );

        return [
            'product' => $product,
            'shoppingLists' => $shoppingListRepository->findAllExceptCurrentForAccountUser($accountUser),
            'currentShoppingList' => $shoppingListRepository->findCurrentForAccountUser($accountUser),
            'form' => $this->createForm(FrontendLineItemType::NAME, $lineItem)->createView(),
        ];
    }
}

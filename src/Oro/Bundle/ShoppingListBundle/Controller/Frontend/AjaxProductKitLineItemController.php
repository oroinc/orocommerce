<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Type\ProductKitLineItemType;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Model\Factory\ShoppingListLineItemsHolderFactory;
use Oro\Bundle\ShoppingListBundle\ProductKit\Checker\ProductKitAvailabilityChecker;
use Oro\Bundle\ShoppingListBundle\ProductKit\Factory\ProductKitLineItemFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller that manages product kits line items via AJAX requests.
 */
class AjaxProductKitLineItemController extends AbstractController
{
    private ProductKitAvailabilityChecker $productKitAvailabilityChecker;

    private CurrentShoppingListManager $currentShoppingListManager;

    private ShoppingListManager $shoppingListManager;

    private ProductKitLineItemFactory $productKitLineItemFactory;

    private SubtotalProviderInterface $lineItemNotPricedSubtotalProvider;

    private ShoppingListLineItemsHolderFactory $lineItemsHolderFactory;

    public function __construct(
        ProductKitAvailabilityChecker $productKitAvailabilityChecker,
        CurrentShoppingListManager $currentShoppingListManager,
        ShoppingListManager $shoppingListManager,
        ProductKitLineItemFactory $productKitLineItemFactory,
        SubtotalProviderInterface $lineItemNotPricedSubtotalProvider,
        ShoppingListLineItemsHolderFactory $lineItemsHolderFactory
    ) {
        $this->productKitAvailabilityChecker = $productKitAvailabilityChecker;
        $this->currentShoppingListManager = $currentShoppingListManager;
        $this->shoppingListManager = $shoppingListManager;
        $this->productKitLineItemFactory = $productKitLineItemFactory;
        $this->lineItemNotPricedSubtotalProvider = $lineItemNotPricedSubtotalProvider;
        $this->lineItemsHolderFactory = $lineItemsHolderFactory;
    }

    /**
     * @Route(
     *      "/create/{productId}",
     *      name="oro_shopping_list_frontend_product_kit_line_item_create",
     *      requirements={"productId"="\d+"},
     *      methods={"GET","POST"}
     * )
     * @ParamConverter("product", options={"id"="productId"})
     * @AclAncestor("oro_product_frontend_view")
     * @Layout()
     */
    public function createAction(Product $product, Request $request): Response|array
    {
        $constraintViolations = null;
        if (!$this->productKitAvailabilityChecker->isAvailableForPurchase($product, $constraintViolations)) {
            $messages = [];
            if ($constraintViolations !== null) {
                foreach ($constraintViolations as $constraintViolation) {
                    $messages['error'][] = $constraintViolation->getMessage();
                }
            }

            return new JsonResponse(['success' => false, 'messages' => $messages], 400);
        }

        /** @var ShoppingList|null $shoppingList */
        $shoppingList = $this->currentShoppingListManager
            ->getForCurrentUser((int)$request->get('shoppingListId'), true);

        if ($shoppingList === null || !$this->isGranted('EDIT', $shoppingList)) {
            throw $this->createAccessDeniedException();
        }

        $productKitLineItem = $this->productKitLineItemFactory->createProductKitLineItem($product);

        $form = $this->createForm(
            ProductKitLineItemType::class,
            $productKitLineItem,
            ['validation_groups' => ['Default', 'add_product', 'add_product_kit_line_item']]
        );
        $form->handleRequest($request);

        if ($request->get('getSubtotal', false)) {
            $subtotal = $this->lineItemNotPricedSubtotalProvider
                ->getSubtotal($this->lineItemsHolderFactory->createFromLineItems([$productKitLineItem]))
                ->toArray();

            return new JsonResponse([
                'success' => true,
                'subtotal' => $subtotal
            ]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->shoppingListManager->addLineItem($productKitLineItem, $shoppingList);

            return new JsonResponse(['success' => true, 'messages' => []]);
        }

        return [
            'data' => [
                'lineItem' => $productKitLineItem,
                'product' => $product,
                'shoppingList' => $shoppingList,
                'form' => $form->createView(),
            ],
        ];
    }
}

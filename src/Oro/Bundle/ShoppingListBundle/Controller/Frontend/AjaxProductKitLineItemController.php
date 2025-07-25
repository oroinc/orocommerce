<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LayoutBundle\Attribute\Layout;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderFactory\ProductLineItemsHolderFactoryInterface;
use Oro\Bundle\ProductBundle\ProductKit\Checker\ProductKitAvailabilityChecker;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Handler\LineItemHandler;
use Oro\Bundle\ShoppingListBundle\Form\Type\ProductKitLineItemType;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\ProductKit\Factory\ProductKitLineItemFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Controller that manages product kits line items via AJAX requests.
 */
class AjaxProductKitLineItemController extends AbstractLineItemController
{
    public function __construct(
        private ProductKitAvailabilityChecker $productKitAvailabilityChecker,
        private CurrentShoppingListManager $currentShoppingListManager,
        private ShoppingListManager $shoppingListManager,
        private ProductKitLineItemFactory $productKitLineItemFactory,
        private SubtotalProviderInterface $lineItemNotPricedSubtotalProvider,
        private ProductLineItemsHolderFactoryInterface $lineItemsHolderFactory,
        private ManagerRegistry $managerRegistry,
        private ValidatorInterface $validator
    ) {
    }

    #[Route(
        path: '/create/{productId}',
        name: 'oro_shopping_list_frontend_product_kit_line_item_create',
        requirements: ['productId' => '\d+'],
        methods: ['GET', 'POST']
    )]
    #[Layout]
    #[ParamConverter('product', options: ['id' => 'productId'])]
    #[AclAncestor('oro_product_frontend_view')]
    public function createAction(Product $product, Request $request): Response|array
    {
        $shoppingListId = $request->get('shoppingListId');
        $shoppingListId = $shoppingListId ? (int)$shoppingListId : null;
        $shoppingList = $this->currentShoppingListManager->getForCurrentUser($shoppingListId, true);
        $productKitLineItem = $this->productKitLineItemFactory->createProductKitLineItem(
            $product,
            null,
            null,
            $shoppingList
        );

        return $this->update($productKitLineItem, $request, true);
    }

    #[Route(
        path: '/update/{id}',
        name: 'oro_shopping_list_frontend_product_kit_line_item_update',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    #[Layout]
    #[ParamConverter('productKitLineItem', options: ['id' => 'id'])]
    #[AclAncestor('oro_shopping_list_frontend_update')]
    public function updateAction(LineItem $productKitLineItem, Request $request): Response|array
    {
        $this->productKitLineItemFactory->addKitItemLineItemsAvailableForPurchase($productKitLineItem);

        return $this->update($productKitLineItem, $request);
    }

    private function update(LineItem $productKitLineItem, Request $request, bool $isCreate = false): Response|array
    {
        $product = $productKitLineItem->getProduct();

        $constraintViolations = null;
        if (!$this->productKitAvailabilityChecker->isAvailable($product, $constraintViolations)) {
            $messages = [];
            if ($constraintViolations !== null) {
                foreach ($constraintViolations as $constraintViolation) {
                    $messages['error'][] = $constraintViolation->getMessage();
                }
            }

            return new JsonResponse(['successful' => false, 'messages' => $messages], 400);
        }

        $shoppingList = $productKitLineItem->getShoppingList();
        $this->checkShoppingListAcl($shoppingList);

        $form = $this->createForm(
            ProductKitLineItemType::class,
            $productKitLineItem,
            ['validation_groups' => new GroupSequence(['Default', 'add_product_kit_line_item'])]
        );

        if ($request->get('getSubtotal', false)) {
            $form->handleRequest($request);
            $subtotal = $this->lineItemNotPricedSubtotalProvider
                ->getSubtotal($this->lineItemsHolderFactory->createFromLineItems([$productKitLineItem]))
                ->toArray();

            return new JsonResponse([
                'successful' => true,
                'subtotal' => $subtotal,
            ]);
        }

        $handler = new LineItemHandler(
            $form,
            $request,
            $this->managerRegistry,
            $this->shoppingListManager,
            $this->currentShoppingListManager,
            $this->validator
        );
        $isFormHandled = $handler->process($productKitLineItem);
        if ($isFormHandled) {
            return new JsonResponse(
                $this->getSuccessResponse(
                    $shoppingList,
                    $product,
                    $isCreate
                        ? 'oro.frontend.shoppinglist.product_kit_line_item.added_to_shopping_list'
                        : 'oro.frontend.shoppinglist.product_kit_line_item.updated_in_shopping_list',
                )
            );
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

    /**
     * @throws AccessDeniedException
     */
    private function checkShoppingListAcl(?ShoppingList $shoppingList = null): void
    {
        if ($shoppingList === null || !$this->isGranted(BasicPermission::EDIT, $shoppingList)) {
            throw $this->createAccessDeniedException();
        }
    }

    #[Route(
        path: '/in-shopping-lists/{productId}',
        name: 'oro_shopping_list_frontend_product_kit_in_shopping_lists',
        requirements: ['productId' => '\d+'],
        methods: ['GET', 'POST']
    )]
    #[Layout]
    #[ParamConverter('product', options: ['id' => 'productId'])]
    #[AclAncestor('oro_product_frontend_view')]
    public function inShoppingListsAction(Product $product, Request $request): Response|array
    {
        return [
            'data' => [
                'product' => $product,
            ],
        ];
    }
}

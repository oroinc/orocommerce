<?php

namespace Oro\Bundle\ShoppingListBundle\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Symfony\Component\Form\Form;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Handles batch adding products to a shopping list.
 */
class ShoppingListLineItemHandler implements ResetInterface
{
    private const FLUSH_BATCH_SIZE = 100;

    private ManagerRegistry $doctrine;
    private ShoppingListManager $shoppingListManager;
    private CurrentShoppingListManager $currentShoppingListManager;
    private AuthorizationCheckerInterface $authorizationChecker;
    private TokenAccessorInterface $tokenAccessor;
    private FeatureChecker $featureChecker;
    private ProductManager $productManager;
    private AclHelper $aclHelper;
    private array $productUnits = [];

    public function __construct(
        ManagerRegistry $doctrine,
        ShoppingListManager $shoppingListManager,
        CurrentShoppingListManager $currentShoppingListManager,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        FeatureChecker $featureChecker,
        ProductManager $productManager,
        AclHelper $aclHelper
    ) {
        $this->doctrine = $doctrine;
        $this->shoppingListManager = $shoppingListManager;
        $this->currentShoppingListManager = $currentShoppingListManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->featureChecker = $featureChecker;
        $this->productManager = $productManager;
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param ShoppingList $shoppingList
     * @param int[]        $productIds
     * @param array        $productUnitsWithQuantities [product id => [unit code => quantity, ...], ...]
     *
     * @return int Added entities count
     */
    public function createForShoppingList(
        ShoppingList $shoppingList,
        array $productIds = [],
        array $productUnitsWithQuantities = []
    ): int {
        if (!$this->isAllowed($shoppingList)) {
            throw new AccessDeniedException();
        }

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->doctrine->getManagerForClass(Product::class);
        $productsRepo = $entityManager->getRepository(Product::class);
        $unitOfWork = $entityManager->getUnitOfWork();

        $queryBuilder = $productsRepo->getProductsQueryBuilder($productIds);
        $queryBuilder = $this->productManager->restrictQueryBuilder($queryBuilder, []);
        $products = $this->aclHelper->apply($queryBuilder)->toIterable();

        $lineItems = [[]];
        /** @var Product $product */
        foreach ($products as $product) {
            $unitOfWork->markReadOnly($product);
            $productId = $product->getId();
            if (isset($productUnitsWithQuantities[$productId])) {
                $productLineItems = $this->createLineItemsWithQuantityAndUnit(
                    $product,
                    $shoppingList,
                    $productUnitsWithQuantities[$productId]
                );
                if ($productLineItems) {
                    $lineItems[] = $productLineItems;
                }
            } else {
                $lineItems[] = [
                    $this->createLineItem($shoppingList, $product, $product->getPrimaryUnitPrecision()->getUnit())
                ];
            }
        }
        $lineItems = array_merge(...$lineItems);

        return $this->shoppingListManager->bulkAddLineItems($lineItems, $shoppingList, self::FLUSH_BATCH_SIZE);
    }

    /**
     * @param Product      $product
     * @param ShoppingList $shoppingList
     * @param array        $unitsWithQuantities [unit code => quantity, ...]
     *
     * @return LineItem[]
     */
    private function createLineItemsWithQuantityAndUnit(
        Product $product,
        ShoppingList $shoppingList,
        array $unitsWithQuantities
    ): array {
        $lineItems = [];
        foreach ($unitsWithQuantities as $unitCode => $quantity) {
            $lineItem = $this->createLineItem($shoppingList, $product, $this->getProductUnit($unitCode));
            $lineItem->setQuantity($quantity);
            $lineItems[] = $lineItem;
        }

        return $lineItems;
    }

    private function createLineItem(ShoppingList $shoppingList, Product $product, ProductUnit $unit): LineItem
    {
        $lineItem = new LineItem();
        $lineItem->setCustomerUser($shoppingList->getCustomerUser());
        $lineItem->setOrganization($shoppingList->getOrganization());
        $lineItem->setProduct($product);
        $lineItem->setUnit($unit);

        return $lineItem;
    }

    private function getProductUnit(string $unitCode): ProductUnit
    {
        if (!isset($this->productUnits[$unitCode])) {
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $this->doctrine->getManagerForClass(ProductUnit::class);
            $this->productUnits[$unitCode] = $entityManager->getReference(ProductUnit::class, $unitCode);
        }

        return $this->productUnits[$unitCode];
    }

    public function prepareLineItemWithProduct(CustomerUser $customerUser, Product $product): LineItem
    {
        $shoppingList = $this->currentShoppingListManager->getCurrent();

        $lineItem = new LineItem();
        $lineItem->setProduct($product);
        $lineItem->setCustomerUser($customerUser);
        if (null !== $shoppingList) {
            $lineItem->setShoppingList($shoppingList);
        }

        return $lineItem;
    }

    public function processLineItem(LineItem $lineItem, Form $form): void
    {
        $shoppingList = $form->get('lineItem')->get('shoppingList')->getData();

        if (!$shoppingList) {
            $name = $form->get('lineItem')->get('shoppingListLabel')->getData();

            $shoppingList = $this->currentShoppingListManager->createCurrent($name);
        }

        $lineItem->setShoppingList($shoppingList);

        $this->shoppingListManager->addLineItem($lineItem, $shoppingList);
    }

    public function isAllowed(ShoppingList $shoppingList = null): bool
    {
        if (!$this->tokenAccessor->hasUser() && !$this->isAllowedForGuest()) {
            return false;
        }

        $isAllowed = $this->authorizationChecker->isGranted('oro_shopping_list_frontend_update');

        if (!$shoppingList) {
            return $isAllowed;
        }

        return $isAllowed && $this->authorizationChecker->isGranted('EDIT', $shoppingList);
    }

    public function getShoppingList(?int $shoppingListId = null): ShoppingList
    {
        return $this->currentShoppingListManager->getForCurrentUser($shoppingListId, true);
    }

    public function isAllowedForGuest(): bool
    {
        $isAllowed = false;
        if ($this->tokenAccessor->getToken() instanceof AnonymousCustomerUserToken) {
            $isAllowed = $this->featureChecker->isFeatureEnabled('guest_shopping_list');
        }

        return $isAllowed;
    }

    #[\Override]
    public function reset(): void
    {
        $this->productUnits = [];
    }
}

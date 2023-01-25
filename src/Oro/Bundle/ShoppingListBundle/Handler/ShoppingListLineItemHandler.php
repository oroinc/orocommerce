<?php

namespace Oro\Bundle\ShoppingListBundle\Handler;

use Doctrine\ORM\EntityManager;
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
    const FLUSH_BATCH_SIZE = 100;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var ShoppingListManager */
    protected $shoppingListManager;

    /** @var CurrentShoppingListManager */
    protected $currentShoppingListManager;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var FeatureChecker */
    protected $featureChecker;

    /** @var string */
    protected $productClass;

    /** @var string */
    protected $shoppingListClass;

    /** @var string */
    protected $productUnitClass;

    /** @var ProductManager */
    protected $productManager;

    /** @var AclHelper */
    private $aclHelper;

    private array $productUnits = [];

    public function __construct(
        ManagerRegistry $managerRegistry,
        ShoppingListManager $shoppingListManager,
        CurrentShoppingListManager $currentShoppingListManager,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        FeatureChecker $featureChecker,
        ProductManager $productManager,
        AclHelper $aclHelper
    ) {
        $this->managerRegistry = $managerRegistry;
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
     * @param array $productIds
     * @param array $productUnitsWithQuantities
     * @return int Added entities count
     */
    public function createForShoppingList(
        ShoppingList $shoppingList,
        array $productIds = [],
        array $productUnitsWithQuantities = []
    ) {
        if (!$this->isAllowed($shoppingList)) {
            throw new AccessDeniedException();
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->managerRegistry->getManagerForClass($this->productClass);
        $productsRepo = $entityManager->getRepository($this->productClass);
        $unitOfWork = $entityManager->getUnitOfWork();

        $queryBuilder = $productsRepo->getProductsQueryBuilder($productIds);
        $queryBuilder = $this->productManager->restrictQueryBuilder($queryBuilder, []);
        $iterableResult = $this->aclHelper->apply($queryBuilder)->toIterable();
        $lineItems = [[]];

        $skus = array_map('mb_strtoupper', array_keys($productUnitsWithQuantities));
        $values = array_values($productUnitsWithQuantities);
        $productUnitsWithQuantities = array_combine($skus, $values);

        /** @var Product $product */
        foreach ($iterableResult as $product) {
            $unitOfWork->markReadOnly($product);
            $upperSku = mb_strtoupper($product->getSku());
            if (isset($productUnitsWithQuantities[$upperSku])) {
                $productLineItems = $this->createLineItemsWithQuantityAndUnit(
                    $product,
                    $shoppingList,
                    $productUnitsWithQuantities[$upperSku]
                );

                if ($productLineItems) {
                    $lineItems[] = $productLineItems;
                }

                continue;
            }

            $lineItem = (new LineItem())
                ->setCustomerUser($shoppingList->getCustomerUser())
                ->setOrganization($shoppingList->getOrganization())
                ->setProduct($product)
                ->setUnit($product->getPrimaryUnitPrecision()->getUnit());

            $lineItems[] = [$lineItem];
        }

        return $this->shoppingListManager->bulkAddLineItems(
            array_merge(...$lineItems),
            $shoppingList,
            self::FLUSH_BATCH_SIZE
        );
    }

    /**
     * @param Product $product
     * @param ShoppingList $shoppingList
     * @param array $unitsWithQuantities
     * @return LineItem[]|null
     */
    protected function createLineItemsWithQuantityAndUnit(
        Product $product,
        ShoppingList $shoppingList,
        array $unitsWithQuantities
    ) {
        $lineItems = [];

        foreach ($unitsWithQuantities as $unitCode => $quantity) {
            $lineItem = (new LineItem())
                ->setCustomerUser($shoppingList->getCustomerUser())
                ->setOrganization($shoppingList->getOrganization())
                ->setQuantity($quantity)
                ->setProduct($product);

            $lineItem->setUnit($this->getProductUnit($unitCode));
            $lineItems[] = $lineItem;
        }

        return $lineItems;
    }

    private function getProductUnit(string $unitCode): ProductUnit
    {
        if (!isset($this->productUnits[$unitCode])) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->managerRegistry->getManagerForClass($this->productUnitClass);
            $this->productUnits[$unitCode] = $entityManager->getReference(ProductUnit::class, $unitCode);
        }

        return $this->productUnits[$unitCode];
    }

    /**
     * @param CustomerUser $customerUser
     * @param Product $product
     * @return LineItem
     */
    public function prepareLineItemWithProduct(CustomerUser $customerUser, Product $product)
    {
        $shoppingList = $this->currentShoppingListManager->getCurrent();

        $lineItem = new LineItem();
        $lineItem
            ->setProduct($product)
            ->setCustomerUser($customerUser);

        if (null !== $shoppingList) {
            $lineItem->setShoppingList($shoppingList);
        }

        return $lineItem;
    }

    public function processLineItem(LineItem $lineItem, Form $form)
    {
        $shoppingList = $form->get('lineItem')->get('shoppingList')->getData();

        if (!$shoppingList) {
            $name = $form->get('lineItem')->get('shoppingListLabel')->getData();

            $shoppingList = $this->currentShoppingListManager->createCurrent($name);
        }

        $lineItem->setShoppingList($shoppingList);

        $this->shoppingListManager->addLineItem($lineItem, $shoppingList);
    }

    /**
     * @param ShoppingList|null $shoppingList
     * @return bool
     */
    public function isAllowed(ShoppingList $shoppingList = null)
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

    /**
     * @param mixed $shoppingListId
     * @return ShoppingList
     */
    public function getShoppingList($shoppingListId = null)
    {
        return $this->currentShoppingListManager->getForCurrentUser($shoppingListId, true);
    }

    /**
     * @param string $productClass
     */
    public function setProductClass($productClass)
    {
        $this->productClass = $productClass;
    }

    /**
     * @param string $shoppingListClass
     */
    public function setShoppingListClass($shoppingListClass)
    {
        $this->shoppingListClass = $shoppingListClass;
    }

    /**
     * @param string $productUnitClass
     */
    public function setProductUnitClass($productUnitClass)
    {
        $this->productUnitClass = $productUnitClass;
    }

    /**
     * @return bool
     */
    public function isAllowedForGuest()
    {
        $isAllowed = false;

        if ($this->tokenAccessor->getToken() instanceof AnonymousCustomerUserToken) {
            $isAllowed = $this->featureChecker->isFeatureEnabled('guest_shopping_list');
        }

        return $isAllowed;
    }

    public function reset(): void
    {
        $this->productUnits = [];
    }
}

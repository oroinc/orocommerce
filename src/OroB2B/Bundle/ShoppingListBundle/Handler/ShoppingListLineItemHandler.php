<?php

namespace OroB2B\Bundle\ShoppingListBundle\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class ShoppingListLineItemHandler
{
    const FLUSH_BATCH_SIZE = 100;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var ShoppingListManager */
    protected $shoppingListManager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var string */
    protected $productClass;

    /** @var string */
    protected $shoppingListClass;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param ShoppingListManager $shoppingListManager
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        ShoppingListManager $shoppingListManager,
        SecurityFacade $securityFacade
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->shoppingListManager = $shoppingListManager;
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param ShoppingList $shoppingList
     * @param array $productIds
     * @param array $productQuantities
     * @return int Added entities count
     */
    public function createForShoppingList(
        ShoppingList $shoppingList,
        array $productIds = [],
        array $productQuantities = []
    ) {
        if (!$this->isAllowed($shoppingList)) {
            throw new AccessDeniedException();
        }

        /** @var ProductRepository $productsRepo */
        $productsRepo = $this->managerRegistry->getManagerForClass($this->productClass)
            ->getRepository($this->productClass);

        $iterableResult = $productsRepo->getProductsQueryBuilder($productIds)->getQuery()->iterate();
        $lineItems = [];
        foreach ($iterableResult as $entityArray) {
            /** @var Product $product */
            $product = reset($entityArray);
            /** @var ProductUnitPrecision $unitPrecision */
            $unitPrecision = $product->getUnitPrecisions()->first();

            $lineItem = (new LineItem())
                ->setAccountUser($shoppingList->getAccountUser())
                ->setOrganization($shoppingList->getOrganization())
                ->setProduct($product)
                ->setUnit($unitPrecision->getUnit());

            $productId = $product->getId();
            if (array_key_exists($productId, $productQuantities)) {
                $lineItem->setQuantity($productQuantities[$productId]);
            }

            $lineItems[] = $lineItem;
        }

        return $this->shoppingListManager->bulkAddLineItems($lineItems, $shoppingList, self::FLUSH_BATCH_SIZE);
    }

    /**
     * @param ShoppingList|null $shoppingList
     * @return bool
     */
    public function isAllowed(ShoppingList $shoppingList = null)
    {
        if (!$this->securityFacade->hasLoggedUser()) {
            return false;
        }

        $isAllowed = $this->securityFacade->isGranted('orob2b_shopping_list_line_item_frontend_add');

        if (!$shoppingList) {
            return $isAllowed;
        }

        return $isAllowed && $this->securityFacade->isGranted('EDIT', $shoppingList);
    }

    /**
     * @param mixed $shoppingListId
     * @return ShoppingList
     */
    public function getShoppingList($shoppingListId = null)
    {
        return $this->shoppingListManager->getForCurrentUser($shoppingListId);
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
}

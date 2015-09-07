<?php

namespace OroB2B\Bundle\ShoppingListBundle\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
     * @return int Added entities count
     *
     * @throws AccessDeniedException If action is not granted
     */
    public function createForShoppingList(ShoppingList $shoppingList, array $productIds = [])
    {
        if (!$this->securityFacade->isGranted('EDIT', $shoppingList)
            || !$this->securityFacade->isGranted('orob2b_shopping_list_line_item_frontend_add')
        ) {
            throw new AccessDeniedException();
        }

        /** @var ProductRepository $productsRepo */
        $productsRepo = $this->managerRegistry->getManagerForClass($this->productClass)
            ->getRepository($this->productClass);

        $iterableResult = $productsRepo->getProductsQueryBuilder($productIds)->getQuery()->iterate();
        $lineItems = [];
        foreach ($iterableResult as $entityArray) {
            /** @var Product $entity */
            $entity = $entityArray[0];
            /** @var ProductUnitPrecision $unitPrecision */
            $unitPrecision = $entity->getUnitPrecisions()->first();

            $lineItems[] = (new LineItem())
                ->setAccountUser($shoppingList->getAccountUser())
                ->setOrganization($shoppingList->getOrganization())
                ->setProduct($entity)
                ->setQuantity(1)
                ->setUnit($unitPrecision->getUnit());
        }

        return $this->shoppingListManager->bulkAddLineItems($lineItems, $shoppingList, self::FLUSH_BATCH_SIZE);
    }

    /**
     * @param mixed $shoppingListId
     * @return ShoppingList
     */
    public function getShoppingList($shoppingListId)
    {
        if (!filter_var($shoppingListId, FILTER_VALIDATE_INT)) {
            throw new \InvalidArgumentException('Wrong ShoppingList id');
        }

        if (!$shoppingListId || $shoppingListId < 0) {
            throw new \InvalidArgumentException('Wrong ShoppingList id');
        }

        /** @var EntityManager $em */
        $em = $this->managerRegistry->getManagerForClass($this->shoppingListClass);

        return $em->getReference($this->shoppingListClass, $shoppingListId);
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

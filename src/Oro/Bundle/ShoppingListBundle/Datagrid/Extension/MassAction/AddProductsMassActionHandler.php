<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponseInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Generator\MessageGenerator;
use Oro\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;

/**
 * DataGrid mass action handler that add products to shopping list.
 */
class AddProductsMassActionHandler implements MassActionHandlerInterface
{
    private MessageGenerator $messageGenerator;
    private ShoppingListLineItemHandler $shoppingListLineItemHandler;
    private ManagerRegistry $doctrine;
    private ProductShoppingListsDataProvider $productShoppingListsDataProvider;
    private AclHelper $aclHelper;

    public function __construct(
        ShoppingListLineItemHandler $shoppingListLineItemHandler,
        MessageGenerator $messageGenerator,
        ManagerRegistry $doctrine,
        ProductShoppingListsDataProvider $productShoppingListsDataProvider,
        AclHelper $aclHelper
    ) {
        $this->shoppingListLineItemHandler = $shoppingListLineItemHandler;
        $this->messageGenerator = $messageGenerator;
        $this->doctrine = $doctrine;
        $this->productShoppingListsDataProvider = $productShoppingListsDataProvider;
        $this->aclHelper = $aclHelper;
    }

    #[\Override]
    public function handle(MassActionHandlerArgs $args): MassActionResponseInterface
    {
        $shoppingList = $this->getShoppingList($args);
        $productIds = $this->getProductIds($args);

        if (null === $shoppingList || !$productIds || !$this->shoppingListLineItemHandler->isAllowed()) {
            return $this->generateResponse($args);
        }

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(ShoppingList::class);
        $em->beginTransaction();

        try {
            if (!$shoppingList->getId()) {
                $em->persist($shoppingList);
                $em->flush();
            }

            $addedCnt = $this->shoppingListLineItemHandler->createForShoppingList(
                $shoppingList,
                $productIds,
                $this->getUnitsAndQuantities($args)
            );

            $em->commit();

            return $this->generateResponse(
                $args,
                $addedCnt,
                $shoppingList->getId(),
                $this->getProductsShoppingLists($productIds)
            );
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
    }

    private function generateResponse(
        MassActionHandlerArgs $args,
        int $entitiesCount = 0,
        ?int $shoppingListId = null,
        array $productsShoppingLists = []
    ): MassActionResponse {
        $transChoiceKey = $args->getMassAction()->getOptions()->offsetGetByPath(
            '[messages][success]',
            'oro.shoppinglist.actions.add_success_message'
        );

        return new MassActionResponse(
            $entitiesCount > 0 && $shoppingListId,
            $this->messageGenerator->getSuccessMessage($shoppingListId, $entitiesCount, $transChoiceKey),
            [
                'count' => $entitiesCount,
                'products' => $productsShoppingLists,
            ]
        );
    }

    /**
     * @param int[] $productIds
     *
     * @return array [product id => ['id' => product id, 'shopping_lists' => [shopping list, ...], ...], ...]
     */
    private function getProductsShoppingLists(array $productIds): array
    {
        $qb = $this->doctrine->getRepository(Product::class)->getProductsQueryBuilder($productIds);
        $qb->orderBy('p.id');

        $products = $this->aclHelper->apply($qb)->getResult();

        $shoppingListsByProducts = $this->productShoppingListsDataProvider->getProductsUnitsQuantity($products);
        $productsShoppingLists = [];

        foreach ($shoppingListsByProducts as $productId => $shoppingLists) {
            $productsShoppingLists[$productId] = [
                'id' => $productId,
                'shopping_lists' => $shoppingLists,
            ];
        }

        return $productsShoppingLists;
    }

    private function getShoppingList(MassActionHandlerArgs $args): ?ShoppingList
    {
        $data = $args->getData();

        return $data['shoppingList'] ?? null;
    }

    /**
     * @return int[]
     */
    private function getProductIds(MassActionHandlerArgs $args): array
    {
        $data = $args->getData();

        $productIds = [];
        if (\array_key_exists('values', $data) && $data['values'] && !$this->isAllSelected($data)) {
            $values = explode(',', $data['values']);
            foreach ($values as $val) {
                $productIds[] = (int)$val;
            }
        }

        return $productIds;
    }

    private function isAllSelected(array $data): bool
    {
        return \array_key_exists('inset', $data) && (int)$data['inset'] === 0;
    }

    /**
     * @return array [product id => [unit code => quantity, ...], ...]
     */
    private function getUnitsAndQuantities(MassActionHandlerArgs $args): array
    {
        $data = $args->getData();

        $unitsAndQuantities = [];
        if (isset($data['units_and_quantities'])) {
            $unitsAndQuantities = json_decode($data['units_and_quantities'], true);
        }

        return $unitsAndQuantities;
    }
}

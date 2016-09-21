<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Add to frontend products grid information about how much qty of some unit were added to current shopping list
 */
class FrontendProductDatagridListener
{
    const COLUMN_LINE_ITEMS = 'shopping_lists';

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        SecurityFacade $securityFacade
    ) {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event)
    {
        $config = $event->getConfig();

        $config->offsetAddToArrayByPath(
            '[properties]',
            [
                self::COLUMN_LINE_ITEMS => [
                    'type' => 'field',
                    'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY,
                ],
            ]
        );
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        $accountUser = $this->getLoggedAccountUser();
        if (!$accountUser) {
            return;
        }

        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        $em = $event->getQuery()->getEntityManager();
        $shoppingList = $this->getCurrentShoppingList($em, $accountUser);
        if (!$shoppingList || count($records) === 0) {
            return;
        }

        $groupedUnits = $this->getGroupedLineItems($records, $em, $accountUser, $shoppingList);
        foreach ($records as $record) {
            $productId = $record->getValue('id');
            if (array_key_exists($productId, $groupedUnits)) {
                $record->addData([self::COLUMN_LINE_ITEMS => $groupedUnits[$productId]]);
            }
        }
    }

    /**
     * @param EntityManagerInterface $em
     * @param AccountUser $accountUser
     * @return null|ShoppingList
     */
    protected function getCurrentShoppingList(EntityManagerInterface $em, AccountUser $accountUser)
    {
        /** @var ShoppingListRepository $repository */
        $repository = $em->getRepository('OroShoppingListBundle:ShoppingList');

        return $repository->findAvailableForAccountUser($accountUser);
    }

    /**
     * @return AccountUser|null
     */
    protected function getLoggedAccountUser()
    {
        $user = $this->securityFacade->getLoggedUser();
        if (!$user instanceof AccountUser) {
            return null;
        }

        return $user;
    }

    /**
     * @param ResultRecord[] $records
     * @param EntityManagerInterface $em
     * @param AccountUser $accountUser
     * @param ShoppingList $currentShoppingList
     * @return array
     */
    protected function getGroupedLineItems(
        array $records,
        EntityManagerInterface $em,
        AccountUser $accountUser,
        ShoppingList $currentShoppingList
    ) {
        /** @var LineItemRepository $lineItemRepository */
        $lineItemRepository = $em->getRepository('OroShoppingListBundle:LineItem');
        /** @var LineItem[] $lineItems */
        $lineItems = $lineItemRepository->getProductItemsWithShoppingListNames(
            array_map(
                function (ResultRecord $record) {
                    return $record->getValue('id');
                },
                $records
            ),
            $accountUser
        );

        $groupedUnits = [];
        $shoppingListLabels = [];
        foreach ($lineItems as $lineItem) {
            $shoppingListId = $lineItem->getShoppingList()->getId();
            $productId = $lineItem->getProduct()->getId();
            $groupedUnits[$productId][$shoppingListId][] = [
                'line_item_id' => $lineItem->getId(),
                'unit' => $lineItem->getProductUnitCode(),
                'quantity' => $lineItem->getQuantity()
            ];
            if (!isset($shoppingListLabels[$shoppingListId])) {
                $shoppingListLabels[$shoppingListId] = $lineItem->getShoppingList()->getLabel();
            }
        }

        $productShoppingLists = [];
        $activeShoppingListId = $currentShoppingList->getId();
        foreach ($groupedUnits as $productId => $productGroupedUnits) {
            /* Active shopping list goes first*/
            if (isset($productGroupedUnits[$activeShoppingListId])) {
                $productShoppingLists[$productId][] = [
                    'shopping_list_id' => $activeShoppingListId,
                    'shopping_list_label' => $shoppingListLabels[$activeShoppingListId],
                    'is_current' => true,
                    'line_items' => $groupedUnits[$productId][$activeShoppingListId],
                ];
                unset($productGroupedUnits[$activeShoppingListId]);
            }

            foreach ($productGroupedUnits as $shoppingListId => $lineItems) {
                $productShoppingLists[$productId][] = [
                    'shopping_list_id' => $shoppingListId,
                    'shopping_list_label' => $shoppingListLabels[$shoppingListId],
                    'is_current' => false,
                    'line_items' => $lineItems,
                ];
            }
        }
        unset($lineItems);
        return $productShoppingLists;
    }
}

<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Add to frontend products grid information about how much qty of some unit were added to current shopping list
 */
class FrontendProductDatagridListener
{
    const COLUMN_LINE_ITEMS = 'shopping_lists';

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @param SecurityFacade $securityFacade
     * @param AclHelper $aclHelper
     * @param RegistryInterface $manager
     */
    public function __construct(SecurityFacade $securityFacade, AclHelper $aclHelper, RegistryInterface $manager)
    {
        $this->securityFacade = $securityFacade;
        $this->aclHelper = $aclHelper;
        $this->registry = $manager;
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
                    'type'          => 'field',
                    'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY,
                ],
            ]
        );
    }

    /**
     * @param SearchResultAfter $event
     */
    public function onResultAfter(SearchResultAfter $event)
    {
        if (!($this->securityFacade->getLoggedUser() instanceof CustomerUser)) {
            return;
        }

        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        $shoppingList = $this->getCurrentShoppingList();
        if (!$shoppingList || count($records) === 0) {
            return;
        }

        $groupedUnits = $this->getGroupedLineItems($records, $shoppingList);
        foreach ($records as $record) {
            $productId = $record->getValue('id');
            if (array_key_exists($productId, $groupedUnits)) {
                $record->addData([self::COLUMN_LINE_ITEMS => $groupedUnits[$productId]]);
            }
        }
    }

    /**
     * @return null|ShoppingList
     */
    protected function getCurrentShoppingList()
    {
        /** @var ShoppingListRepository $repository */
        $repository = $this->registry->getRepository('OroShoppingListBundle:ShoppingList');

        return $repository->findAvailableForCustomerUser($this->aclHelper);
    }

    /**
     * @param ResultRecord[] $records
     * @param ShoppingList   $currentShoppingList
     * @return array
     */
    protected function getGroupedLineItems(
        array $records,
        ShoppingList $currentShoppingList
    ) {
        /** @var LineItemRepository $lineItemRepository */
        $lineItemRepository = $this->registry->getRepository('OroShoppingListBundle:LineItem');
        /** @var LineItem[] $lineItems */
        $lineItems = $lineItemRepository->getProductItemsWithShoppingListNames(
            $this->aclHelper,
            array_map(
                function (ResultRecord $record) {
                    return $record->getValue('id');
                },
                $records
            )
        );

        $groupedUnits       = [];
        $shoppingListLabels = [];
        foreach ($lineItems as $lineItem) {
            $shoppingListId                              = $lineItem->getShoppingList()->getId();
            $productId                                   = $lineItem->getProduct()->getId();
            $groupedUnits[$productId][$shoppingListId][] = [
                'id'       => $lineItem->getId(),
                'unit'     => $lineItem->getProductUnitCode(),
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
                    'id'         => $activeShoppingListId,
                    'label'      => $shoppingListLabels[$activeShoppingListId],
                    'is_current' => true,
                    'line_items' => $groupedUnits[$productId][$activeShoppingListId],
                ];
                unset($productGroupedUnits[$activeShoppingListId]);
            }

            foreach ($productGroupedUnits as $shoppingListId => $lineItems) {
                $productShoppingLists[$productId][] = [
                    'id'         => $shoppingListId,
                    'label'      => $shoppingListLabels[$shoppingListId],
                    'is_current' => false,
                    'line_items' => $lineItems,
                ];
            }
        }
        unset($lineItems);
        return $productShoppingLists;
    }
}

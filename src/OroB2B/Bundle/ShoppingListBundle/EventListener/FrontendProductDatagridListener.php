<?php

namespace OroB2B\Bundle\ShoppingListBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Add to frontend products grid information about how much qty of some unit were added to current shopping list
 */
class FrontendProductDatagridListener
{
    const COLUMN_LINE_ITEMS = 'current_shopping_list_line_items';

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
        $repository = $em->getRepository('OroB2BShoppingListBundle:ShoppingList');

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
     * @param ShoppingList $shoppingList
     * @return array
     */
    protected function getGroupedLineItems(
        array $records,
        EntityManagerInterface $em,
        AccountUser $accountUser,
        ShoppingList $shoppingList
    ) {
        /** @var LineItemRepository $lineItemRepository */
        $lineItemRepository = $em->getRepository('OroB2BShoppingListBundle:LineItem');
        /** @var LineItem[] $lineItems */
        $lineItems = $lineItemRepository->findBy(
            [
                'product' => array_map(
                    function (ResultRecord $record) {
                        return $record->getValue('id');
                    },
                    $records
                ),
                'accountUser' => $accountUser,
                'shoppingList' => $shoppingList
            ]
        );

        $groupedUnits = [];
        foreach ($lineItems as $lineItem) {
            $groupedUnits[$lineItem->getProduct()->getId()][$lineItem->getProductUnitCode()] = $lineItem->getQuantity();
        }
        unset($lineItems);

        return $groupedUnits;
    }
}

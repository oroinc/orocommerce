<?php

namespace OroB2B\Bundle\ShoppingListBundle\EventListener;

use Doctrine\ORM\Query\Expr;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class FrontendProductDatagridListener
{
    const COLUMN_LINE_ITEMS = 'current_shopping_list_line_items';

    const BLOCK_SEPARATOR = '{blk}';
    const DATA_SEPARATOR = '{unt}';

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @var string
     */
    protected $shoppingListClassName;

    /**
     * @var string
     */
    protected $lineItemClassName;

    /**
     * @param SecurityFacade $securityFacade
     * @param RegistryInterface $registry
     * @param string $shoppingListClassName
     * @param string $lineItemClassName
     */
    public function __construct(
        SecurityFacade $securityFacade,
        RegistryInterface $registry,
        $shoppingListClassName,
        $lineItemClassName
    ) {
        $this->securityFacade = $securityFacade;
        $this->registry = $registry;
        $this->shoppingListClassName = $shoppingListClassName;
        $this->lineItemClassName = $lineItemClassName;
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event)
    {
        $config = $event->getConfig();

        $this->applyUpdatesToConfig(
            $config,
            [
                '[source][query][select]' => [
                    sprintf(
                        'GROUP_CONCAT(CONCAT(IDENTITY(shoppingListLineItem.shoppingList), %s, ' .
                        'IDENTITY(shoppingListLineItem.unit), %s, shoppingListLineItem.quantity) SEPARATOR %s) as %s',
                        (new Expr())->literal(self::BLOCK_SEPARATOR),
                        (new Expr())->literal(self::BLOCK_SEPARATOR),
                        (new Expr())->literal(self::DATA_SEPARATOR),
                        self::COLUMN_LINE_ITEMS
                    )
                ],
                '[source][query][join][left]' => [
                    [
                        'join' => $this->lineItemClassName,
                        'alias' => 'shoppingListLineItem',
                        'conditionType' => Expr\Join::WITH,
                        'condition' => 'product.id = IDENTITY(shoppingListLineItem.product)'
                    ]
                ],
                '[properties]' => [
                    self::COLUMN_LINE_ITEMS => [
                        'type' => 'field',
                        'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY
                    ]
                ]
            ]
        );
    }

    /**
     * @param DatagridConfiguration $config
     * @param array $updates
     */
    protected function applyUpdatesToConfig(DatagridConfiguration $config, array $updates)
    {
        foreach ($updates as $path => $update) {
            $config->offsetAddToArrayByPath($path, $update);
        }
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        $shoppingList = $this->getCurrentShoppingList();

        // handle line item units
        foreach ($records as $record) {
            $units = [];

            if ($shoppingList) {
                $concatenatedUnits = $record->getValue(self::COLUMN_LINE_ITEMS);
                if ($concatenatedUnits) {
                    $concatenatedUnits = array_map(
                        function ($unit) {
                            return explode(self::BLOCK_SEPARATOR, $unit);
                        },
                        explode(self::DATA_SEPARATOR, $concatenatedUnits)
                    );

                    foreach ($concatenatedUnits as $unit) {
                        if ((int)$unit[0] !== $shoppingList->getId()) {
                            continue;
                        }

                        $units[$unit[1]] = $unit[2];
                    }
                }
            }

            $record->addData([self::COLUMN_LINE_ITEMS => $units]);
        }
    }

    /**
     * @return null|ShoppingList
     */
    protected function getCurrentShoppingList()
    {
        $shoppingList = null;

        $user = $this->securityFacade->getLoggedUser();
        if ($user instanceof AccountUser) {
            /** @var ShoppingListRepository $repository */
            $repository = $this->registry->getRepository($this->shoppingListClassName);

            $shoppingList = $repository->findAvailableForAccountUser($user);
        }

        return $shoppingList;
    }
}

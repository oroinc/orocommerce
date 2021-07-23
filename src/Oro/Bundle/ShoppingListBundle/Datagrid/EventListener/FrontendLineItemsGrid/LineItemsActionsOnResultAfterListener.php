<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\EventListener\FrontendLineItemsGrid;

use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Adds action configuration for simple and grouped rows.
 */
class LineItemsActionsOnResultAfterListener
{
    /** @var array */
    private $cachedIsGranted = [];

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function onResultAfter(OrmResultAfter $event): void
    {
        $records = $event->getRecords();
        if (!$records) {
            return;
        }

        $isEditGranted = $this->isEditGranted($event);
        $defaultActionsConfig = [
            'add_notes' => false,
            'edit_notes' => false,
            'update_configurable' => false,
            'delete' => $isEditGranted,
        ];

        foreach ($records as $record) {
            if ($record->getValue('isConfigurable')) {
                $actionsConfig = $defaultActionsConfig;
                $actionsConfig['update_configurable'] = $isEditGranted && $record->getValue('isMatrixFormAvailable');

                $subData = (array)$record->getValue('subData') ?: [];
                foreach ($subData as &$lineItemData) {
                    $subDataActionsConfig = $defaultActionsConfig;
                    $subDataActionsConfig['add_notes'] = $isEditGranted
                        && (string)($lineItemData['notes'] ?? '') === '';

                    $lineItemData['action_configuration'] = $subDataActionsConfig;
                }
                unset($lineItemData);

                $record->setValue('subData', $subData);
            } else {
                $actionsConfig = $defaultActionsConfig;
                $actionsConfig['add_notes'] = $isEditGranted && (string)$record->getValue('notes') === '';
            }

            $record->setValue('action_configuration', $actionsConfig);
        }
    }

    private function isEditGranted(OrmResultAfter $event): bool
    {
        $shoppingList = $this->getShoppingList($event);

        return $shoppingList ? $this->isEditPermissionGrantedForShoppingList($shoppingList) : false;
    }

    private function getShoppingList(OrmResultAfter $event): ?ShoppingList
    {
        $shoppingListId = $event->getDatagrid()->getParameters()->get('shopping_list_id');
        if ($shoppingListId) {
            /** @var ShoppingList $shoppingList */
            $shoppingList = $event->getQuery()->getEntityManager()->find(ShoppingList::class, $shoppingListId);
        }

        return $shoppingList ?? null;
    }

    private function isEditPermissionGrantedForShoppingList(ShoppingList $shoppingList): bool
    {
        $shoppingListId = $shoppingList->getId();
        if (!isset($this->cachedIsGranted[$shoppingListId])) {
            $this->cachedIsGranted[$shoppingListId] = $this->authorizationChecker->isGranted(
                'oro_shopping_list_frontend_update',
                $shoppingList
            );
        }

        return $this->cachedIsGranted[$shoppingListId];
    }
}

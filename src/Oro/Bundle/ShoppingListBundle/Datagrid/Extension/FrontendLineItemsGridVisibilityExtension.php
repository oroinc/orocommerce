<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Extension;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Adds warning message to configuration when some line items are invisible.
 */
class FrontendLineItemsGridVisibilityExtension extends AbstractExtension
{
    /** @var string[] */
    private const SUPPORTED_GRIDS = [
        'frontend-customer-user-shopping-list-grid',
        'frontend-customer-user-shopping-list-edit-grid',
    ];
    const HIDDEN_LINE_ITEMS_OPTION = '[options][hiddenLineItems]';

    private array $cache = [];

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly ResolvedProductVisibilityProvider $visibilityProvider
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config): bool
    {
        return \in_array($config->getName(), static::SUPPORTED_GRIDS, true) && parent::isApplicable($config);
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config): void
    {
        $shoppingListId = $this->getShoppingListId();
        if (!$shoppingListId) {
            return;
        }

        $shoppingList = $this->getShoppingList($shoppingListId);
        if (!$shoppingList) {
            return;
        }

        $lineItems = $shoppingList->getLineItems();
        $this->prefetchProductsVisibility($lineItems);

        $manager = $this->doctrine->getManagerForClass(LineItem::class);
        $removeProdSkus = [];
        /** @var LineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            if (!$this->isVisible($lineItem)) {
                $removeProdSkus[] = $lineItem->getProduct()->getSkuUppercase();
                $shoppingList->removeLineItem($lineItem);

                $manager->remove($lineItem);
            }
        }

        if (!empty($removeProdSkus)) {
            $manager->flush($shoppingList);
            $config->offsetSetByPath(self::HIDDEN_LINE_ITEMS_OPTION, $removeProdSkus);
        }
    }

    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        if (!empty($config->offsetGetByPath(self::HIDDEN_LINE_ITEMS_OPTION))) {
            $result->offsetSetByPath(
                self::HIDDEN_LINE_ITEMS_OPTION,
                $config->offsetGetByPath(self::HIDDEN_LINE_ITEMS_OPTION)
            );
        }
    }

    /**
     * @param Collection $lineItems
     * @return void
     */
    private function prefetchProductsVisibility(Collection $lineItems): void
    {
        $productIds = [];
        foreach ($lineItems as $lineItem) {
            if ($lineItem instanceof LineItem && $lineItem->getProduct()) {
                $productIds[] = $lineItem->getProduct()->getId();
            }
        }

        $this->visibilityProvider->prefetch(array_unique($productIds));
    }

    private function isVisible(LineItem $lineItem): bool
    {
        $product = $lineItem->getProduct();
        if (!$product) {
            return true;
        }

        return $this->authorizationChecker->isGranted('VIEW', $product);
    }

    private function getShoppingListId(): int
    {
        return (int) $this->parameters->get('shopping_list_id');
    }

    private function getShoppingList(int $shoppingListId): ?ShoppingList
    {
        if (!isset($this->cache['shoppingLists'][$shoppingListId])) {
            $this->cache['shoppingLists'][$shoppingListId] = $this->doctrine
                ->getRepository(ShoppingList::class)
                ->find($shoppingListId);
        }

        return $this->cache['shoppingLists'][$shoppingListId];
    }
}

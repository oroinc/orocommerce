<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Symfony\Component\HttpFoundation\RequestStack;

class CustomerUserShoppingListsProvider
{
    const DATA_SORT_BY_UPDATED = 'updated';

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var ShoppingListTotalManager
     */
    protected $totalManager;

    /**
     * @var ShoppingListManager
     */
    protected $shoppingListManager;

    /**
     * @param RequestStack $requestStack
     * @param ShoppingListTotalManager $totalManager
     * @param ShoppingListManager $shoppingListManager
     */
    public function __construct(
        RequestStack $requestStack,
        ShoppingListTotalManager $totalManager,
        ShoppingListManager $shoppingListManager
    ) {
        $this->requestStack = $requestStack;
        $this->totalManager = $totalManager;
        $this->shoppingListManager = $shoppingListManager;
    }

    /**
     * @return array
     */
    public function getShoppingLists()
    {
        if (!array_key_exists('shoppingLists', $this->options)) {
            $shoppingLists = $this->shoppingListManager->getShoppingListsWithCurrentFirst($this->getSortOrder());
            $this->totalManager->setSubtotals($shoppingLists, false);
            $this->options['shoppingLists'] = $shoppingLists;
        }

        return $this->options['shoppingLists'];
    }

    /**
     * @return string
     */
    protected function getSortOrder()
    {
        $request = $this->requestStack->getCurrentRequest();
        $sort = $request ? $request->get('shopping_list_sort') : self::DATA_SORT_BY_UPDATED;

        switch ($sort) {
            case self::DATA_SORT_BY_UPDATED:
                $sortOrder['list.updatedAt'] = Criteria::DESC;
                break;
            default:
                $sortOrder['list.id'] = Criteria::ASC;
        }

        return $sortOrder;
    }
}

<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Symfony\Component\HttpFoundation\RequestStack;

use Doctrine\Common\Collections\Criteria;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;

class AccountUserShoppingListsProvider
{
    const DATA_SORT_BY_UPDATED = 'updated';

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var string
     */
    protected $shoppingListClass;

    /**
     * @var ShoppingListTotalManager
     */
    protected $totalManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param SecurityFacade $securityFacade
     * @param RequestStack $requestStack
     * @param ShoppingListTotalManager $totalManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        SecurityFacade $securityFacade,
        RequestStack $requestStack,
        ShoppingListTotalManager $totalManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->securityFacade = $securityFacade;
        $this->requestStack = $requestStack;
        $this->totalManager = $totalManager;
    }

    /**
     * @param string $shoppingListClass
     */
    public function setShoppingListClass($shoppingListClass)
    {
        $this->shoppingListClass = $shoppingListClass;
    }

    /**
     * @return array
     */
    public function getShoppingLists()
    {
        if (!array_key_exists('shoppingLists', $this->options)) {
            $accountUser = $this->securityFacade->getLoggedUser();
            $shoppingLists = [];
            if ($accountUser) {
                /** @var ShoppingListRepository $shoppingListRepository */
                $shoppingListRepository = $this->doctrineHelper->getEntityRepositoryForClass($this->shoppingListClass);

                /** @var ShoppingList[] $shoppingLists */
                $shoppingLists = $shoppingListRepository->findByUser($accountUser, $this->getSortOrder());
                $this->totalManager->setSubtotals($shoppingLists, false);
            }

            $this->options['shoppingLists'] = $shoppingLists;
        }

        return $this->options['shoppingLists'];
    }

    /**
     * @return string
     */
    protected function getSortOrder()
    {
        $sortOrder = [];
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

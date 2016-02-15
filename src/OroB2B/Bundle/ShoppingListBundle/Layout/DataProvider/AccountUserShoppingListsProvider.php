<?php

namespace OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider;

use Symfony\Component\HttpFoundation\RequestStack;

use Doctrine\Common\Collections\Criteria;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;

class AccountUserShoppingListsProvider implements DataProviderInterface
{
    const DATA_SORT_BY_UPDATED = 'updated';

    /**
     * @var FormAccessor
     */
    protected $data;

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
     * @param DoctrineHelper $doctrineHelper
     * @param SecurityFacade $securityFacade
     * @param RequestStack $requestStack
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        SecurityFacade $securityFacade,
        RequestStack $requestStack
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->securityFacade = $securityFacade;
        $this->requestStack = $requestStack;
    }

    /**
     * @param string $shoppingListClass
     */
    public function setShoppingListClass($shoppingListClass)
    {
        $this->shoppingListClass = $shoppingListClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'orob2b_shopping_list_account_user_shopping_lists';
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        if (!$this->data) {
            $this->data = $this->getAccountUserShoppingLists();
        }

        return $this->data;
    }

    /**
     * @return array|null
     * @throws \InvalidArgumentException
     */
    protected function getAccountUserShoppingLists()
    {
        $accountUser = $this->securityFacade->getLoggedUser();
        if (!$accountUser) {
            return null;
        }

        /** @var ShoppingListRepository $shoppingListRepository */
        $shoppingListRepository = $this->doctrineHelper->getEntityRepositoryForClass($this->shoppingListClass);

        /** @var ShoppingList[] $allShoppingLists */
        $allShoppingLists = $shoppingListRepository->findByUser($accountUser, $this->getSortOrder());
        $shoppingListsExceptedCurrent = [];
        $currentShoppingList = null;

        foreach ($allShoppingLists as $shoppingList) {
            if ($shoppingList->isCurrent()) {
                $currentShoppingList = $shoppingList;
            } else {
                $shoppingListsExceptedCurrent[] = $shoppingList;
            }
        }

        return [
            'allShoppingLists' => $allShoppingLists,
            'shoppingListsExceptedCurrent' => $shoppingListsExceptedCurrent,
            'currentShoppingList' => $currentShoppingList,
        ];
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

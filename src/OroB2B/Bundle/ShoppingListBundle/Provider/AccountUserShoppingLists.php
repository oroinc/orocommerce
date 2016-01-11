<?php

namespace OroB2B\Bundle\ShoppingListBundle\Provider;


use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;

class AccountUserShoppingLists implements DataProviderInterface
{
    /** @var FormAccessor */
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
     * @var string
     */
    protected $shoppingListClass;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        SecurityFacade $securityFacade
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->securityFacade = $securityFacade;
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

    protected function getAccountUserShoppingLists()
    {
        $accountUser = $this->securityFacade->getLoggedUser();
        if (!$accountUser) {
            return null;
        }

        /** @var ShoppingListRepository $shoppingListRepository */
        $shoppingListRepository = $this->doctrineHelper->getEntityRepositoryForClass($this->shoppingListClass);

        return [
            'shoppingLists' => $shoppingListRepository->findAllExceptCurrentForAccountUser($accountUser),
            'currentShoppingList' => $shoppingListRepository->findCurrentForAccountUser($accountUser),
        ];
    }
}

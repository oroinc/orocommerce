<?php

namespace OroB2B\Bundle\ShoppingListBundle\Helper;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;

class ShoppingListLineItemHelper
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var string */
    protected $shoppingListClass;

    /**
     * @param ManagerRegistry $registry
     * @param SecurityFacade $securityFacade
     */
    public function __construct(ManagerRegistry $registry, SecurityFacade $securityFacade)
    {
        $this->registry = $registry;
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
     * @return array
     */
    public function getShoppingLists()
    {
        $accountUser = $this->securityFacade->getLoggedUser();

        if (!$accountUser instanceof AccountUser) {
            throw new \RuntimeException('AccountUser required.');
        }

        $repository = $this->getRepository();

        return [
            'shoppingLists' => $repository->findAllExceptCurrentForAccountUser($accountUser),
            'currentShoppingList' => $repository->findCurrentForAccountUser($accountUser)
        ];
    }

    /**
     * @return ShoppingListRepository
     */
    protected function getRepository()
    {
        return $this->registry->getRepository($this->shoppingListClass);
    }
}

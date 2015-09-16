<?php

namespace OroB2B\Bundle\ShoppingListBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;

class ShoppingListManager
{
    /**
     * @var AccountUser
     */
    private $accountUser;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @param ManagerRegistry       $managerRegistry
     * @param TokenStorageInterface $tokenStorage
     * @param TranslatorInterface   $translator
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
    }

    /**
     * Creates current shopping list
     *
     * @param string $label
     *
     * @return ShoppingList
     */
    public function createCurrent($label = '')
    {
        $label = $label !== '' ? $label : $this->translator->trans('orob2b.shoppinglist.default.label');

        $shoppingList = new ShoppingList();
        $shoppingList
            ->setOrganization($this->getAccountUser()->getOrganization())
            ->setAccount($this->getAccountUser()->getAccount())
            ->setAccountUser($this->getAccountUser())
            ->setLabel($label);

        $this->setCurrent($this->getAccountUser(), $shoppingList);

        return $shoppingList;
    }

    /**
     * @param AccountUser  $accountUser
     * @param ShoppingList $shoppingList
     */
    public function setCurrent(AccountUser $accountUser, ShoppingList $shoppingList)
    {
        $em = $this->managerRegistry->getManagerForClass('OroB2BShoppingListBundle:ShoppingList');
        /** @var ShoppingListRepository $shoppingListRepository */
        $shoppingListRepository = $em->getRepository('OroB2BShoppingListBundle:ShoppingList');
        $currentList = $shoppingListRepository->findCurrentForAccountUser($accountUser);

        if ($currentList instanceof ShoppingList && $currentList->getId() !== $shoppingList->getId()) {
            $currentList->setCurrent(false);
        }
        $shoppingList->setCurrent(true);

        $em->persist($shoppingList);
        $em->flush();
    }

    /**
     * @param LineItem          $lineItem
     * @param ShoppingList|null $shoppingList
     * @param bool|true         $flush
     */
    public function addLineItem(LineItem $lineItem, ShoppingList $shoppingList, $flush = true)
    {
        $em = $this->managerRegistry->getManagerForClass('OroB2BShoppingListBundle:LineItem');
        $lineItem->setShoppingList($shoppingList);
        /** @var LineItemRepository $repository */
        $repository = $em->getRepository('OroB2BShoppingListBundle:LineItem');
        $duplicate = $repository->findDuplicate($lineItem);
        if ($duplicate instanceof LineItem && $shoppingList->getId()) {
            $duplicate->setQuantity($duplicate->getQuantity() + $lineItem->getQuantity());
        } else {
            $shoppingList->addLineItem($lineItem);
            $em->persist($lineItem);
        }

        if ($flush) {
            $em->flush();
        }
    }

    /**
     * @param array        $lineItems
     * @param ShoppingList $shoppingList
     * @param int          $batchSize
     *
     * @return int
     */
    public function bulkAddLineItems(array $lineItems, ShoppingList $shoppingList, $batchSize)
    {
        $iteration = 0;
        foreach ($lineItems as $iteration => $lineItem) {
            $flush = $iteration % $batchSize === 0 || count($lineItems) === $iteration + 1;
            $this->addLineItem($lineItem, $shoppingList, $flush);
        }

        return $iteration + 1;
    }

    /**
     * @param int $shoppingListId
     *
     * @return ShoppingList
     */
    public function getForCurrentUser($shoppingListId = null)
    {
        $em = $this->managerRegistry->getManagerForClass('OroB2BShoppingListBundle:ShoppingList');
        /** @var ShoppingListRepository $repository */
        $repository = $em->getRepository('OroB2BShoppingListBundle:ShoppingList');
        $shoppingList = null === $shoppingListId
            ? $repository->findCurrentForAccountUser($this->getAccountUser())
            : $repository->findByUserAndId($this->getAccountUser(), $shoppingListId);

        if (!($shoppingList instanceof ShoppingList)) {
            $shoppingList = $this->createCurrent();
        }

        return $shoppingList;
    }

    /**
     * @return string|AccountUser
     */
    protected function getAccountUser()
    {
        if (!$this->accountUser) {
            $token = $this->tokenStorage->getToken();
            if ($token) {
                $this->accountUser = $token->getUser();
            }
        }

        if (!$this->accountUser || !is_object($this->accountUser)) {
            throw new AccessDeniedException();
        }

        return $this->accountUser;
    }
}

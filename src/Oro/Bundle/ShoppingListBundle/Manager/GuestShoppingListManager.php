<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles logic related to getting/creating shopping lists for customer visitors
 */
class GuestShoppingListManager
{
    use FeatureCheckerHolderTrait;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var WebsiteManager */
    private $websiteManager;

    /** @var TranslatorInterface */
    private $translator;

    /** @var ConfigManager */
    private $configManager;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        TokenStorageInterface $tokenStorage,
        WebsiteManager $websiteManager,
        TranslatorInterface $translator,
        ConfigManager $configManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->tokenStorage = $tokenStorage;
        $this->websiteManager = $websiteManager;
        $this->translator = $translator;
        $this->configManager = $configManager;
    }

    /**
     * @param int|null $userId
     * @return null|object
     */
    public function getDefaultUser($userId)
    {
        $userRepository = $this->doctrineHelper->getEntityRepository(User::class);

        if ($userId) {
            return $userRepository->find($userId);
        }

        return $userRepository->findOneBy([], ['id' => 'ASC']);
    }

    /**
     * @return bool
     */
    public function isGuestShoppingListAvailable()
    {
        return $this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken && $this->isFeaturesEnabled();
    }

    public function findExistingShoppingListForCustomerVisitor(): ?ShoppingList
    {
        $token = $this->tokenStorage->getToken();
        if (!$token instanceof AnonymousCustomerUserToken) {
            throw new \LogicException(sprintf('Token should be instance of %s.', AnonymousCustomerUserToken::class));
        }

        /** @var CustomerVisitor $customerVisitor */
        $customerVisitor = $token->getVisitor();
        if ($customerVisitor === null) {
            throw new \LogicException('Customer visitor is empty.');
        }

        $currentWebsite = $this->websiteManager->getCurrentWebsite();
        if ($currentWebsite === null) {
            throw new \LogicException('Current website is empty.');
        }

        /** @var ShoppingList[] $shoppingLists */
        $shoppingLists = $customerVisitor->getShoppingLists();
        foreach ($shoppingLists as $shoppingList) {
            if (!$website = $shoppingList->getWebsite()) {
                continue;
            }

            if ($website->getId() === $currentWebsite->getId()) {
                return $shoppingList->setCurrent(true);
            }
        }

        return null;
    }

    /**
     * @return ShoppingList|null
     */
    public function getShoppingListForCustomerVisitor()
    {
        $shoppingList = $this->findExistingShoppingListForCustomerVisitor();

        if (null === $shoppingList
            && $this->configManager->get('oro_shopping_list.create_shopping_list_for_new_guest')
        ) {
            $shoppingList = $this->createShoppingListForCustomerVisitor();
        }

        return $shoppingList;
    }

    /**
     * @return ShoppingList
     */
    public function createAndGetShoppingListForCustomerVisitor()
    {
        $shoppingList = $this->getShoppingListForCustomerVisitor();

        return $shoppingList ?: $this->createShoppingListForCustomerVisitor();
    }

    /**
     * @return ShoppingList
     */
    public function createShoppingListForCustomerVisitor()
    {
        $token = $this->tokenStorage->getToken();

        /** @var CustomerVisitor $customerVisitor */
        $customerVisitor = $token->getVisitor();

        $currentWebsite = $this->websiteManager->getCurrentWebsite();

        $shoppingList = new ShoppingList();
        $shoppingList
            ->setOrganization($currentWebsite->getOrganization())
            ->setCustomer(null)
            ->setCustomerUser(null)
            ->setWebsite($currentWebsite)
            ->setCurrent(true);

        $shoppingList->setLabel($this->translator->trans('oro.shoppinglist.default.label'));

        $em = $this->doctrineHelper->getEntityManager(ShoppingList::class);
        $em->persist($shoppingList);

        //Link customer visitor to shopping list
        $customerVisitor->addShoppingList($shoppingList);
        $em->flush([$shoppingList, $customerVisitor]);

        return $shoppingList;
    }

    /**
     * @return array
     */
    public function getShoppingListsForCustomerVisitor()
    {
        $guestShoppingList = $this->getShoppingListForCustomerVisitor();

        if ($guestShoppingList === null) {
            return [];
        }

        return [$guestShoppingList];
    }
}

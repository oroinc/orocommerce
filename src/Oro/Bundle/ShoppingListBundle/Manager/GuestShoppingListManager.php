<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

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

    public function __construct(
        private DoctrineHelper $doctrineHelper,
        private TokenStorageInterface $tokenStorage,
        private WebsiteManager $websiteManager,
        private TranslatorInterface $translator
    ) {
    }

    public function getDefaultUser(?int $userId): ?User
    {
        $userRepository = $this->doctrineHelper->getEntityRepository(User::class);
        if ($userId) {
            return $userRepository->find($userId);
        }

        return $userRepository->findOneBy([], ['id' => 'ASC']);
    }

    public function isGuestShoppingListAvailable(): bool
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
            $website = $shoppingList->getWebsite();
            if (!$website) {
                continue;
            }

            if ($website->getId() === $currentWebsite->getId()) {
                return $shoppingList->setCurrent(true);
            }
        }

        return null;
    }

    public function getShoppingListForCustomerVisitor(): ?ShoppingList
    {
        return $this->findExistingShoppingListForCustomerVisitor();
    }

    public function createAndGetShoppingListForCustomerVisitor(): ShoppingList
    {
        $shoppingList = $this->getShoppingListForCustomerVisitor();

        return $shoppingList ?: $this->createShoppingListForCustomerVisitor();
    }

    public function createShoppingListForCustomerVisitor(): ShoppingList
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
        if (null === $customerVisitor->getId()) {
            $em->persist($customerVisitor);
        }
        $em->persist($shoppingList);

        //Link customer visitor to shopping list
        $customerVisitor->addShoppingList($shoppingList);
        $em->flush([$shoppingList, $customerVisitor]);

        return $shoppingList;
    }

    /**
     * @return ShoppingList[]
     */
    public function getShoppingListsForCustomerVisitor(): array
    {
        $guestShoppingList = $this->getShoppingListForCustomerVisitor();
        if ($guestShoppingList === null) {
            return [];
        }

        return [$guestShoppingList];
    }
}

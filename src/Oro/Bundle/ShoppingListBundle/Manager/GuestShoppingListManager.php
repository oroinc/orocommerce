<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

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

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param TokenStorageInterface $tokenStorage
     * @param WebsiteManager $websiteManager
     * @param TranslatorInterface $translator
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        TokenStorageInterface $tokenStorage,
        WebsiteManager $websiteManager,
        TranslatorInterface $translator
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->tokenStorage = $tokenStorage;
        $this->websiteManager = $websiteManager;
        $this->translator = $translator;
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

    /**
     * @return ShoppingList
     */
    public function getShoppingListForCustomerVisitor()
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

        //Create new SL if no one still exists
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
}

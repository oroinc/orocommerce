<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\SaveParentEntity;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CheckoutBundle\Api\Model\CheckoutStartOptions;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Model\CheckoutBySourceCriteriaManipulatorInterface;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Creates a new Checkout entity based on the current parent entity
 * or load existing Checkout entity if it is already exists.
 */
class HandleStartCheckoutFromAnotherEntity implements ProcessorInterface
{
    public function __construct(
        private readonly string $sourceEntity,
        private readonly CheckoutBySourceCriteriaManipulatorInterface $checkoutBySourceCriteriaManipulator,
        private readonly DoctrineHelper $doctrineHelper,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UserCurrencyManager $userCurrencyManager,
        private readonly WebsiteManager $websiteManager
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ChangeSubresourceContext $context */

        $sourceCriteria = [$this->sourceEntity => $context->getParentEntity()];
        $currentUser = $this->getCurrentUser();
        $currentCurrency = $this->userCurrencyManager->getUserCurrency();
        $checkout = $this->checkoutBySourceCriteriaManipulator->findCheckout(
            $sourceCriteria,
            $currentUser,
            $currentCurrency
        );
        if (null === $checkout) {
            $checkout = $this->checkoutBySourceCriteriaManipulator->createCheckout(
                $this->websiteManager->getCurrentWebsite(),
                $sourceCriteria,
                $currentUser,
                $currentCurrency
            );
            $em = $this->getEntityManager();
            $em->persist($checkout);
            $em->flush();
            $context->setExisting(false);
        } elseif ($this->getOptions($context)->actualize) {
            $checkout = $this->checkoutBySourceCriteriaManipulator->actualizeCheckout(
                $checkout,
                $this->websiteManager->getCurrentWebsite(),
                $sourceCriteria,
                $currentCurrency
            );
            $this->getEntityManager()->flush();
        }
        $context->setResult([$context->getAssociationName() => $checkout]);
        $context->setProcessed(SaveParentEntity::OPERATION_NAME);
    }

    private function getOptions(ChangeSubresourceContext $context): CheckoutStartOptions
    {
        return $context->getResult()[$context->getAssociationName()];
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrineHelper->getEntityManagerForClass(Checkout::class);
    }

    private function getCurrentUser(): ?UserInterface
    {
        $token = $this->tokenStorage->getToken();
        if ($token instanceof AnonymousCustomerUserToken) {
            return $token->getVisitor()?->getCustomerUser();
        }

        $user = $token->getUser();
        if ($user instanceof UserInterface) {
            return $user;
        }

        return null;
    }
}

<?php

namespace Oro\Bundle\ConsentBundle\Action;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Feature\Voter\FeatureVoter;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Save accepted consents to current customer user
 */
class SaveAcceptedConsentsAction extends AbstractAction
{
    use FeatureCheckerHolderTrait;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var PropertyPath */
    private $acceptedConsents;

    /**
     * @param ContextAccessor $contextAccessor
     * @param TokenStorageInterface $tokenStorage
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        TokenStorageInterface $tokenStorage,
        DoctrineHelper $doctrineHelper
    ) {
        parent::__construct($contextAccessor);

        $this->tokenStorage = $tokenStorage;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Saves accepted consents for Existing CustomerUser only
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        if (!$this->featureChecker->isFeatureEnabled(FeatureVoter::FEATURE_NAME)) {
            return;
        }

        /** @var ConsentAcceptance[] $acceptedConsents */
        $acceptedConsents = $this->contextAccessor->getValue($context, $this->acceptedConsents);
        $customerUser = $this->getCustomerUser();
        if ($customerUser && $acceptedConsents) {
            /** @var ArrayCollection $customerUserAcceptedConsents */
            $acceptedConsentsKeys = $this->getCustomerUserAcceptedConsentsKeys($customerUser);
            foreach ($acceptedConsents as $acceptance) {
                if (!array_key_exists($this->getAcceptedConsentKey($acceptance), $acceptedConsentsKeys)) {
                    $customerUser->addAcceptedConsent($this->getConsentAcceptance($acceptance));
                }
            }
            $manager = $this->doctrineHelper->getEntityManager(CustomerUser::class);
            $manager->flush($customerUser);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (!array_key_exists('acceptedConsents', $options)) {
            throw new InvalidParameterException('"acceptedConsents" parameter is required');
        }

        $this->acceptedConsents = $options['acceptedConsents'];

        return $this;
    }

    /**
     * @return CustomerUser|object|null
     */
    private function getCustomerUser()
    {
        $token = $this->tokenStorage->getToken();
        if ($token) {
            if ($token instanceof AnonymousCustomerUserToken) {
                $customerUser = $token->getVisitor()->getCustomerUser();
            } else {
                $customerUser = $token->getUser();
            }

            return $customerUser instanceof CustomerUser ? $customerUser : null;
        }

        return null;
    }

    /**
     * We require this "reinitialization" after proxies fetched and unserialized from workflow. Doctrine can not handle
     * it correct and this causes sql error
     * @param ConsentAcceptance $consentAcceptance
     *
     * @return ConsentAcceptance|null
     */
    private function getConsentAcceptance(ConsentAcceptance $consentAcceptance)
    {
        $emConsent = $this->doctrineHelper->getEntityManager(Consent::class);

        $initializedConsent = $emConsent->find(Consent::class, $consentAcceptance->getConsent()->getId());
        $consentAcceptance->setConsent($initializedConsent);

        if ($consentAcceptance->getLandingPage()) {
            $initializedLandingPage = $emConsent->find(Page::class, $consentAcceptance->getLandingPage()->getId());
            $consentAcceptance->setLandingPage($initializedLandingPage);
        }

        return $consentAcceptance;
    }

    /**
     * @param CustomerUser|object $customerUser
     *
     * @return array
     */
    private function getCustomerUserAcceptedConsentsKeys(CustomerUser $customerUser)
    {
        $keys = [];

        /** @var ConsentAcceptance[] $acceptedConsents */
        $acceptedConsents = $customerUser->getAcceptedConsents();
        foreach ($acceptedConsents as $acceptedConsent) {
            $keys[$this->getAcceptedConsentKey($acceptedConsent)] = true;
        }

        return $keys;
    }

    /**
     * @param ConsentAcceptance $consentAcceptance
     *
     * @return string
     */
    private function getAcceptedConsentKey(ConsentAcceptance $consentAcceptance)
    {
        $landingPageId = null;
        if ($consentAcceptance->getLandingPage()) {
            $landingPageId = $consentAcceptance->getLandingPage()->getId();
        }

        return sprintf('%s_%s', $consentAcceptance->getConsent()->getId(), $landingPageId);
    }
}

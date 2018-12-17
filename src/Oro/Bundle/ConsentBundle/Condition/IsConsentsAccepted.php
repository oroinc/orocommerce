<?php

namespace Oro\Bundle\ConsentBundle\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConsentBundle\Feature\Voter\FeatureVoter;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\ConsentBundle\Provider\EnabledConsentProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Workflow condition that check that all customer user consents was accepted
 */
class IsConsentsAccepted extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;
    use FeatureCheckerHolderTrait;

    const NAME = 'is_consents_accepted';

    /** @var EnabledConsentProvider */
    private $enabledConsentProvider;

    /** @var ConsentAcceptanceProvider */
    private $consentAcceptanceProvider;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var PropertyPath */
    private $acceptedConsents;

    /**
     * @param EnabledConsentProvider $enabledConsentProvider
     * @param ConsentAcceptanceProvider $consentAcceptanceProvider
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        EnabledConsentProvider $enabledConsentProvider,
        ConsentAcceptanceProvider $consentAcceptanceProvider,
        TokenStorageInterface $tokenStorage
    ) {
        $this->enabledConsentProvider = $enabledConsentProvider;
        $this->consentAcceptanceProvider = $consentAcceptanceProvider;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (array_key_exists('acceptedConsents', $options)) {
            $this->acceptedConsents = $options['acceptedConsents'];
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        if ($this->featureChecker->isFeatureEnabled(FeatureVoter::FEATURE_NAME)) {
            if ($this->isCustomerUser()) {
                $consentAcceptances = $this->consentAcceptanceProvider->getCustomerConsentAcceptances();
            } else {
                $consentAcceptances = $this->resolveValue($context, $this->acceptedConsents);
                if ($consentAcceptances instanceof ArrayCollection) {
                    $consentAcceptances = $consentAcceptances->toArray();
                }
            }

            return !$this->enabledConsentProvider->getUnacceptedRequiredConsents((array) $consentAcceptances);
        }

        return true;
    }

    /**
     * @return bool
     */
    private function isCustomerUser()
    {
        $token = $this->tokenStorage->getToken();
        if ($token) {
            return $token->getUser() instanceof CustomerUser;
        }

        return false;
    }
}

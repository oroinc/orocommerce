<?php

namespace Oro\Bundle\ConsentBundle\Condition;

use Oro\Bundle\ConsentBundle\Feature\Voter\FeatureVoter;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\ConsentBundle\Provider\EnabledConsentProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
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

    const NAME = 'is_consents_accepted';

    /** @var FeatureChecker */
    private $featureChecker;

    /** @var EnabledConsentProvider */
    private $enabledConsentProvider;

    /** @var ConsentAcceptanceProvider */
    private $consentAcceptanceProvider;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var PropertyPath */
    private $acceptedConsents;

    /**
     * @param FeatureChecker $featureChecker
     * @param EnabledConsentProvider $enabledConsentProvider
     * @param ConsentAcceptanceProvider $consentAcceptanceProvider
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        FeatureChecker $featureChecker,
        EnabledConsentProvider $enabledConsentProvider,
        ConsentAcceptanceProvider $consentAcceptanceProvider,
        TokenStorageInterface $tokenStorage
    ) {
        $this->featureChecker = $featureChecker;
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
                $consentAcceptances = (array) $this->resolveValue($context, $this->acceptedConsents);
            }

            return !$this->enabledConsentProvider->getUnacceptedRequiredConsents($consentAcceptances);
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

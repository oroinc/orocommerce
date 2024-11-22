<?php

namespace Oro\Bundle\ConsentBundle\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\ConsentBundle\Provider\EnabledConsentProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
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

    private EnabledConsentProvider $enabledConsentProvider;
    private ConsentAcceptanceProvider $consentAcceptanceProvider;
    private TokenStorageInterface $tokenStorage;

    /** @var PropertyPath|mixed */
    private $acceptedConsents;

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
        if (!$this->isFeaturesEnabled()) {
            return true;
        }

        $customerConsentAcceptances = $this->isCustomerUser()
            ? $this->consentAcceptanceProvider->getCustomerConsentAcceptances()
            : [];

        $consentAcceptances = $this->resolveValue($context, $this->acceptedConsents);
        if ($consentAcceptances instanceof ArrayCollection) {
            $consentAcceptances = $consentAcceptances->toArray();
        }
        $consentAcceptances = array_merge($customerConsentAcceptances, (array)$consentAcceptances);

        return !$this->enabledConsentProvider->getUnacceptedRequiredConsents($consentAcceptances);
    }

    private function isCustomerUser(): bool
    {
        return $this->tokenStorage->getToken()?->getUser() instanceof CustomerUser;
    }
}

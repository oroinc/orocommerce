<?php

namespace Oro\Bundle\ConsentBundle\Condition;

use Oro\Bundle\ConsentBundle\Feature\Voter\FeatureVoter;
use Oro\Bundle\ConsentBundle\Provider\ConsentDataProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;

/**
 * Workflow condition that check that all customer user consents was accepted
 */
class IsConsentsAccepted extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'is_consents_accepted';

    /** @var FeatureChecker */
    private $featureChecker;

    /** @var ConsentDataProvider */
    private $consentDataProvider;

    /**
     * @param FeatureChecker $featureChecker
     * @param ConsentDataProvider $consentDataProvider
     */
    public function __construct(FeatureChecker $featureChecker, ConsentDataProvider $consentDataProvider)
    {
        $this->featureChecker = $featureChecker;
        $this->consentDataProvider = $consentDataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
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
            return !$this->consentDataProvider->getNotAcceptedRequiredConsentData();
        }

        return true;
    }
}

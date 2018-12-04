<?php

namespace Oro\Bundle\ConsentBundle\Validator\Constraints;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Filter\AdminConsentContentNodeValidFilter;
use Oro\Bundle\ConsentBundle\Filter\FrontendConsentContentNodeValidFilter;
use Oro\Bundle\ConsentBundle\Filter\RequiredConsentFilter;
use Oro\Bundle\ConsentBundle\Provider\EnabledConsentProvider;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that all required constraints are checked
 */
class RequiredConsentsValidator extends ConstraintValidator
{
    /** @var EnabledConsentProvider */
    private $enabledConsentProvider;

    /** @var LocalizationHelper */
    private $localizationHelper;

    /**
     * @param EnabledConsentProvider $enabledConsentProvider
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(
        EnabledConsentProvider $enabledConsentProvider,
        LocalizationHelper $localizationHelper
    ) {
        $this->enabledConsentProvider = $enabledConsentProvider;
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!is_array($value)) {
            throw new \LogicException("Incorrect type of the value!");
        }

        $checkedConsentIds = array_map(function (ConsentAcceptance $consentAcceptance) {
            return $consentAcceptance->getConsent()->getId();
        }, $value);

        $requiredConsents = $this->enabledConsentProvider->getConsents([
            RequiredConsentFilter::NAME,
            AdminConsentContentNodeValidFilter::NAME,
            FrontendConsentContentNodeValidFilter::NAME
        ]);

        $notCheckedRequiredConsents = array_filter(
            $requiredConsents,
            function (Consent $consent) use ($checkedConsentIds) {
                $result = !in_array($consent->getId(), $checkedConsentIds);
                return $result;
            }
        );

        if (!empty($notCheckedRequiredConsents)) {
            $consentLabels = array_map([$this, 'getConsentsLabels'], $notCheckedRequiredConsents);
            $this->context
                ->buildViolation(
                    $constraint->message,
                    [
                        '{{ consent_names }}' => '"'.implode('", "', $consentLabels).'"',
                    ]
                )
                ->addViolation();
        }
    }

    /**
     * @param Consent $consent
     *
     * @return string
     */
    private function getConsentsLabels(Consent $consent)
    {
        return (string) $this->localizationHelper->getLocalizedValue(
            $consent->getNames()
        );
    }
}

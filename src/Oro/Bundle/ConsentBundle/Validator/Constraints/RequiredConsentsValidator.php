<?php

namespace Oro\Bundle\ConsentBundle\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Provider\EnabledConsentProvider;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that all required constraints are checked
 */
class RequiredConsentsValidator extends ConstraintValidator
{
    protected EnabledConsentProvider $enabledConsentProvider;
    protected LocalizationHelper $localizationHelper;

    public function __construct(
        EnabledConsentProvider $enabledConsentProvider,
        LocalizationHelper $localizationHelper
    ) {
        $this->enabledConsentProvider = $enabledConsentProvider;
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * {@inheritdoc}
     *
     * @param RequiredConsents $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!is_array($value) && !$value instanceof ArrayCollection) {
            throw new \LogicException('Incorrect type of the value!');
        }

        $unacceptedRequiredConsents = $this->enabledConsentProvider
            ->getUnacceptedRequiredConsents($value->toArray());
        if ($unacceptedRequiredConsents) {
            $consentLabels = array_map([$this, 'getConsentsLabels'], $unacceptedRequiredConsents);
            $this->context
                ->buildViolation($constraint->message, [
                    '{{ consent_names }}' => '"'.implode('", "', $consentLabels).'"',
                ])
                ->addViolation();
        }
    }

    private function getConsentsLabels(Consent $consent): string
    {
        return (string) $this->localizationHelper->getLocalizedValue(
            $consent->getNames()
        );
    }
}

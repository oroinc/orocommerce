<?php

namespace Oro\Bundle\ConsentBundle\Filter;

use Oro\Bundle\ConsentBundle\Entity\Consent;

/**
 * Allow to filter required consents
 */
class RequiredConsentFilter implements ConsentFilterInterface
{
    public const NAME = 'required_consent_filter';

    #[\Override]
    public function isConsentPassedFilter(Consent $consent, array $params = [])
    {
        return $consent->isMandatory();
    }

    #[\Override]
    public function getName()
    {
        return self::NAME;
    }
}

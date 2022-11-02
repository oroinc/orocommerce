<?php

namespace Oro\Bundle\ConsentBundle\Filter;

use Oro\Bundle\ConsentBundle\Entity\Consent;

/**
 * Allow to filter required consents
 */
class RequiredConsentFilter implements ConsentFilterInterface
{
    const NAME = 'required_consent_filter';

    /**
     * {@inheritdoc}
     */
    public function isConsentPassedFilter(Consent $consent, array $params = [])
    {
        return $consent->isMandatory();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}

<?php

namespace Oro\Bundle\ConsentBundle\Filter;

use Oro\Bundle\ConsentBundle\Entity\Consent;

/**
 * Filter allows to check whether Consent must not be included in the return result set
 */
interface ConsentFilterInterface
{
    const LOG_ERRORS_PARAMETER = 'log_errors';

    /**
     * `false` result tells that this Consent must not be included in the return result set
     *
     * @param Consent $consent
     * @param array $params
     *
     * @return bool
     */
    public function isConsentPassedFilter(Consent $consent, array $params = []);

    /**
     * @return string
     */
    public function getName();
}

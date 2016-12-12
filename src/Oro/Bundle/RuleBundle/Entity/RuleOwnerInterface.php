<?php

namespace Oro\Bundle\PaymentBundle\Entity;

use Oro\Bundle\RuleBundle\Entity\Rule;

interface RuleOwnerInterface
{
    /**
     * @return Rule
     */
    public function getRule();
}

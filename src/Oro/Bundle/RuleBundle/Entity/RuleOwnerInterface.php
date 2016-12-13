<?php

namespace Oro\Bundle\RuleBundle\Entity;

interface RuleOwnerInterface
{
    /**
     * @return Rule
     */
    public function getRule();
}

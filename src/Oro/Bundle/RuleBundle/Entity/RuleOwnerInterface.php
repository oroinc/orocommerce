<?php

namespace Oro\Bundle\RuleBundle\Entity;

interface RuleOwnerInterface
{
    /**
     * @return RuleInterface
     */
    public function getRule();
}

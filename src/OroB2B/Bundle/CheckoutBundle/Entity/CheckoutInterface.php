<?php

namespace OroB2B\Bundle\CheckoutBundle\Entity;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareInterface;

interface CheckoutInterface extends WorkflowAwareInterface
{
    /**
     * @return CheckoutSource
     */
    public function getSourceEntity();
}

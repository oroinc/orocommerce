<?php

namespace OroB2B\Bundle\CheckoutBundle\Entity;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareInterface;

interface CheckoutInterface extends WorkflowAwareInterface
{
    /**
     * @return CheckoutSource
     */
    public function getSourceEntity();

    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getType();

    /**
     * @param CheckoutSource $source
     * @return $this
     */
    public function setSource(CheckoutSource $source);
}

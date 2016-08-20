<?php

namespace Oro\Bundle\CheckoutBundle\Entity;

interface CheckoutInterface
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
     * @param CheckoutSource $source
     * @return $this
     */
    public function setSource(CheckoutSource $source);

    /**
     * @return CheckoutSource
     */
    public function getSource();
}

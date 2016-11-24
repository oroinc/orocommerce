<?php

namespace Oro\Bundle\CheckoutBundle\Entity;

use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;

interface CheckoutInterface
{
    /**
     * @return CheckoutSourceEntityInterface|null
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

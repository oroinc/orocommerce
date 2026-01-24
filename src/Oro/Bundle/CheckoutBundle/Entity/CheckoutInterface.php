<?php

namespace Oro\Bundle\CheckoutBundle\Entity;

use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 * Defines the contract for checkout entities.
 *
 * Specifies the required methods for checkout entities, including access to source entities,
 * identifiers, and checkout source information.
 */
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

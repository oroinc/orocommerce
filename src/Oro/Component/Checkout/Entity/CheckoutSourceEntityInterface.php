<?php

namespace Oro\Component\Checkout\Entity;

/**
 * Defines the contract for entities that serve as source documents for checkout operations.
 *
 * Implementing classes must provide access to the original source document and its identifier,
 * allowing the checkout process to maintain a reference to the entity that initiated the checkout.
 */
interface CheckoutSourceEntityInterface
{
    /**
     * @return object
     */
    public function getSourceDocument();

    /**
     * @return string
     */
    public function getSourceDocumentIdentifier();
}

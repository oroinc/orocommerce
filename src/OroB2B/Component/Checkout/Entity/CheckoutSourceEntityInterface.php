<?php

namespace OroB2B\Component\Checkout\Entity;

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

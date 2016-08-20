<?php

namespace Oro\Component\Checkout\Entity;

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

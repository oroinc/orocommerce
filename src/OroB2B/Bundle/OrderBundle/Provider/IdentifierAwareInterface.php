<?php

namespace OroB2B\Bundle\OrderBundle\Provider;

interface IdentifierAwareInterface
{
    /**
     * Get Identifier
     *
     * @return string
     */
    public function getIdentifier();
}

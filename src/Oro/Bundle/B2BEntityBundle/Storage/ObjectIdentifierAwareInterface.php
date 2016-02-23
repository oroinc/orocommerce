<?php

namespace Oro\Bundle\B2BEntityBundle\Storage;

interface ObjectIdentifierAwareInterface
{
    /**
     * @return string
     */
    public function getObjectIdentifier();
}

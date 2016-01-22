<?php

namespace Oro\Bundle\B2BEntityBundle\Storage;

trait ObjectIdentifierGeneratorTrait
{
    /**
     * @return string
     */
    public function getObjectIdentifier()
    {
        return spl_object_hash($this);
    }
}

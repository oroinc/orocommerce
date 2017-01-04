<?php

namespace Oro\Component\WebCatalog\Entity;

interface ContentNodeAwareInterface
{
    /**
     * @return ContentNodeInterface
     */
    public function getNode();
}

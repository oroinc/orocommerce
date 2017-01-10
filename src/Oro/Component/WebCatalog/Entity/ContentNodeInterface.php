<?php

namespace Oro\Component\WebCatalog\Entity;

interface ContentNodeInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return ContentNodeInterface[]
     */
    public function getContentVariants();
}

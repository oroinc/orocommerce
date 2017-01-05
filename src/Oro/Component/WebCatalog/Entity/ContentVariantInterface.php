<?php

namespace Oro\Component\WebCatalog\Entity;

interface ContentVariantInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getType();
}

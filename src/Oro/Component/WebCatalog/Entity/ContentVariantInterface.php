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

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type);
}

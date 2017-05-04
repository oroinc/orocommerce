<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Model;

interface ApruveEntityInterface
{
    /**
     * @return array
     */
    public function getData();

    /**
     * @return string|null
     */
    public function getId();
}

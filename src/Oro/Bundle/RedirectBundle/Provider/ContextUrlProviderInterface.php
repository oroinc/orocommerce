<?php

namespace Oro\Bundle\RedirectBundle\Provider;

interface ContextUrlProviderInterface
{
    /**
     * @param mixed $data
     * @return string|null
     */
    public function getUrl($data);
}

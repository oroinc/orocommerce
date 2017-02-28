<?php

namespace Oro\Component\SEO\Provider;

interface VersionAwareInterface
{
    /**
     * @param string $version
     */
    public function setVersion($version);
}

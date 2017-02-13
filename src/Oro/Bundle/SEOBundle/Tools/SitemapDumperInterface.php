<?php

namespace Oro\Bundle\SEOBundle\Tools;

interface SitemapDumperInterface
{
    /**
     * @param array $options
     */
    public function dump(array $options = []);
}

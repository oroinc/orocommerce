<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Filesystem;

use Symfony\Component\Finder\Glob;
use Symfony\Component\Finder\Iterator\MultiplePcreFilterIterator;

/**
 * Filters file names by patterns (a regexp, a glob, or a string).
 */
class FilenameFilterIterator extends MultiplePcreFilterIterator
{
    /**
     * {@inheritDoc}
     */
    public function accept()
    {
        return $this->isAccepted(pathinfo($this->current(), PATHINFO_BASENAME));
    }

    /**
     * {@inheritDoc}
     */
    protected function toRegex($str)
    {
        return $this->isRegex($str) ? $str : Glob::toRegex($str);
    }
}

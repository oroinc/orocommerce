<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Filesystem;

use Symfony\Component\Finder\Glob;
use Symfony\Component\Finder\Iterator\MultiplePcreFilterIterator;

/**
 * Filters file names by patterns (a regexp, a glob, or a string).
 */
class FilenameFilterIterator extends MultiplePcreFilterIterator
{
    #[\Override]
    public function accept(): bool
    {
        return $this->isAccepted(pathinfo($this->current(), PATHINFO_BASENAME));
    }

    #[\Override]
    protected function toRegex(string $str): string
    {
        return $this->isRegex($str) ? $str : Glob::toRegex($str);
    }
}

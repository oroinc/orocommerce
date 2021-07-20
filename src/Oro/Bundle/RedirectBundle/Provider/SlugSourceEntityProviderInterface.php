<?php

namespace Oro\Bundle\RedirectBundle\Provider;

use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;

/**
 * Provides source entity for the slug.
 */
interface SlugSourceEntityProviderInterface
{
    public function getSourceEntityBySlug(Slug $slug): ?SlugAwareInterface;
}

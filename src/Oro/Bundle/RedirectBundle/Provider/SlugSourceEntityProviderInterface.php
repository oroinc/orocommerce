<?php

namespace Oro\Bundle\RedirectBundle\Provider;

use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;

/**
 * Provides source entity for the slug.
 */
interface SlugSourceEntityProviderInterface
{
    /**
     * @param Slug $slug
     * @return SlugAwareInterface|null
     */
    public function getSourceEntityBySlug(Slug $slug): ?SlugAwareInterface;
}

<?php

namespace Oro\Bundle\RedirectBundle\Provider;

use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;

/**
 * Registry for all SlugSourceEntityProviderInterface providers.
 */
class SlugSourceEntityProviderRegistry implements SlugSourceEntityProviderInterface
{
    /**
     * @var SlugSourceEntityProviderInterface[]
     */
    protected $providers;

    /**
     * @param iterable|SlugSourceEntityProviderInterface[] $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceEntityBySlug(Slug $slug): ?SlugAwareInterface
    {
        foreach ($this->providers as $provider) {
            $sourceEntity = $provider->getSourceEntityBySlug($slug);
            if ($sourceEntity) {
                return $sourceEntity;
            }
        }

        return null;
    }
}

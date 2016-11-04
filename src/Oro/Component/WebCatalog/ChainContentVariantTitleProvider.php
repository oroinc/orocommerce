<?php

namespace Oro\Component\WebCatalog;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

class ChainContentVariantTitleProvider implements ContentVariantTitleProviderInterface
{
    /**
     * @var ContentVariantTitleProviderInterface[]
     */
    protected $providers = [];

    /**
     * Registers the given provider in the chain
     *
     * @param ContentVariantTitleProviderInterface $provider
     */
    public function addProvider(ContentVariantTitleProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * Returns the content variant's title of the first content variant in that it will be found
     *
     * @param ArrayCollection|ContentVariantInterface[] $contentVariants
     *
     * @return string|null
     */
    public function getFirstTitle(ArrayCollection $contentVariants)
    {
        foreach ($contentVariants as $contentVariant) {
            $name = $this->getTitle($contentVariant);
            if (null !== $name && '' !== $name) {
                return $name;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle(ContentVariantInterface $contentVariant)
    {
        foreach ($this->providers as $provider) {
            $name = $provider->getTitle($contentVariant);
            if (null !== $name && '' !== $name) {
                return $name;
            }
        }

        return null;
    }
}

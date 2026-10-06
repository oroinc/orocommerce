<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Request;

/**
 * The registry of the embedded fragments from Resources/config/oro/storefront_embedded.yml.
 */
class EmbeddedFragmentRegistry
{
    /**
     * @param array<string, array{route_name: string, layout_block_ids: string[]}> $embeddedFragments
     */
    public function __construct(
        private readonly array $embeddedFragments,
    ) {
    }

    public function hasFragment(string $fragmentName): bool
    {
        return !empty($this->embeddedFragments[$fragmentName]['route_name']);
    }

    /**
     * @throws \InvalidArgumentException When the fragment is unknown
     */
    public function getRouteName(string $fragmentName): string
    {
        $this->assertHasFragment($fragmentName);

        return $this->embeddedFragments[$fragmentName]['route_name'];
    }

    /**
     * @return string[]
     *
     * @throws \InvalidArgumentException When the fragment is unknown
     */
    public function getLayoutBlockIds(string $fragmentName): array
    {
        $this->assertHasFragment($fragmentName);

        return $this->embeddedFragments[$fragmentName]['layout_block_ids'];
    }

    private function assertHasFragment(string $fragmentName): void
    {
        if (!$this->hasFragment($fragmentName)) {
            throw new \InvalidArgumentException(sprintf('Unknown embedded fragment "%s".', $fragmentName));
        }
    }
}

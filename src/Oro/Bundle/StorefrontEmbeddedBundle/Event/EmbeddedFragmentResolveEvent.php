<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when the url of the page an embedded fragment lives on is resolved, before the url is generated.
 */
class EmbeddedFragmentResolveEvent extends Event
{
    public const string CONTEXT_REQUEST = 'request';

    /**
     * @param array<string|int, mixed> $routeParameters
     * @param array<string, mixed> $context
     * @param list<string> $blockIds
     */
    public function __construct(
        private readonly string $fragmentName,
        private readonly string $routeName,
        private array $routeParameters,
        private readonly array $context,
        private array $blockIds,
    ) {
    }

    public function getFragmentName(): string
    {
        return $this->fragmentName;
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    /**
     * @return list<string>
     */
    public function getBlockIds(): array
    {
        return $this->blockIds;
    }

    /**
     * @param list<string> $blockIds
     */
    public function setBlockIds(array $blockIds): void
    {
        $this->blockIds = $blockIds;
    }

    public function addBlockId(string $blockId): void
    {
        $this->blockIds[] = $blockId;
    }

    /**
     * @return array<string|int, mixed>
     */
    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    /**
     * The route parameters reach the router unfiltered: filter untrusted input before setting it,
     * e.g. drop the names that start with '_'.
     *
     * @param array<string|int, mixed> $routeParameters
     */
    public function setRouteParameters(array $routeParameters): void
    {
        $this->routeParameters = $routeParameters;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    public function getContextValue(string $name): mixed
    {
        return $this->context[$name] ?? null;
    }
}

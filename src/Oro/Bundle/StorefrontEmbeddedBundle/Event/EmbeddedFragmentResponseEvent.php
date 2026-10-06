<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a successful embedded fragment response has a layout JSON body, before the body is written back.
 */
class EmbeddedFragmentResponseEvent extends Event
{
    public const string CONTEXT_REQUEST = 'request';

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $content
     */
    public function __construct(
        private readonly string $fragmentName,
        private readonly array $context,
        private array $content,
    ) {
    }

    public function getFragmentName(): string
    {
        return $this->fragmentName;
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

    /**
     * @return array<string, mixed>
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * @param array<string, mixed> $content
     */
    public function setContent(array $content): void
    {
        $this->content = $content;
    }

    public function getContentValue(string $name): mixed
    {
        return $this->content[$name] ?? null;
    }

    public function setContentValue(string $name, mixed $value): void
    {
        $this->content[$name] = $value;
    }

    public function removeContentValue(string $name): void
    {
        unset($this->content[$name]);
    }

    public function hasContentValue(string $name): bool
    {
        return \array_key_exists($name, $this->content);
    }
}

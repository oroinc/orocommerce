<?php

namespace Oro\Bundle\RedirectBundle\Api\Model;

/**
 * Represents a storefront route.
 */
class Route
{
    private string $id;
    private string $url;
    private string $routeName;
    private array $routeParameters;
    private bool $isSlug;
    private ?string $redirectUrl = null;
    private ?int $redirectStatusCode = null;

    public function __construct(
        string $id,
        string $url,
        string $routeName,
        array $routeParameters,
        bool $isSlug
    ) {
        $this->id = $id;
        $this->url = $url;
        $this->routeName = $routeName;
        $this->routeParameters = $routeParameters;
        $this->isSlug = $isSlug;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    public function isSlug(): bool
    {
        return $this->isSlug;
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function getRedirectStatusCode(): ?int
    {
        return $this->redirectStatusCode;
    }

    public function setRedirect(string $url, int $statusCode): void
    {
        $this->redirectUrl = $url;
        $this->redirectStatusCode = $statusCode;
    }
}

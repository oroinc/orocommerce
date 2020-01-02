<?php

namespace Oro\Bundle\RedirectBundle\Api\Model;

/**
 * Represents a storefront route.
 */
class Route
{
    /** @var string */
    private $id;

    /** @var string */
    private $url;

    /** @var string */
    private $routeName;

    /** @var array */
    private $routeParameters;

    /** @var bool */
    private $isSlug;

    /** @var string|null */
    private $redirectUrl;

    /** @var int|null */
    private $redirectStatusCode;

    /**
     * @param string $id
     * @param string $url
     * @param string $routeName
     * @param array  $routeParameters
     * @param bool   $isSlug
     */
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

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getRouteName(): string
    {
        return $this->routeName;
    }

    /**
     * @return array
     */
    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    /**
     * @return bool
     */
    public function isSlug(): bool
    {
        return $this->isSlug;
    }

    /**
     * @return string|null
     */
    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    /**
     * @return int|null
     */
    public function getRedirectStatusCode(): ?int
    {
        return $this->redirectStatusCode;
    }

    /**
     * @param string $url
     * @param int    $statusCode
     */
    public function setRedirect(string $url, int $statusCode): void
    {
        $this->redirectUrl = $url;
        $this->redirectStatusCode = $statusCode;
    }
}

<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Twig;

use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Lets a layout tell whether the storefront is rendering for an embedded host and through which prefix that host
 * serves Oro, which is what the embedded page-config block keys on:
 *   - oro_storefront_embedded_request
 *   - oro_storefront_embedded_proxy_prefix
 */
class EmbeddedRequestExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    /**
     * @return TwigFunction[]
     */
    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('oro_storefront_embedded_request', [$this, 'isEmbeddedRequest']),
            new TwigFunction('oro_storefront_embedded_proxy_prefix', [$this, 'getProxyPrefix']),
        ];
    }

    public function isEmbeddedRequest(): bool
    {
        return $this->getEmbeddedRequestChecker()->isEmbeddedRequest();
    }

    public function getProxyPrefix(): string
    {
        return $this->getEmbeddedRequestChecker()->getProxyPrefix();
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            EmbeddedRequestChecker::class,
        ];
    }

    private function getEmbeddedRequestChecker(): EmbeddedRequestChecker
    {
        return $this->container->get(EmbeddedRequestChecker::class);
    }
}

<?php

namespace Oro\Bundle\VisibilityBundle\Twig;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * TWIG extension providing handy visibility-related functions.
 */
class VisibilityExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_visible_product', [$this, 'isVisibleProduct']),
        ];
    }

    public function isVisibleProduct(Product|int|null $product): bool
    {
        $productId = $product instanceof Product ? $product->getId() : (int) $product;
        if (!$productId) {
            return false;
        }

        return $this->getResolvedProductVisibilityProvider()->isVisible($productId);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ResolvedProductVisibilityProvider::class
        ];
    }

    private function getResolvedProductVisibilityProvider(): ResolvedProductVisibilityProvider
    {
        return $this->container->get(ResolvedProductVisibilityProvider::class);
    }
}

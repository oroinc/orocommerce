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
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_visible_product', [$this, 'isVisibleProduct']),
        ];
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'oro_visibility.provider.resolved_product_visibility_provider' => ResolvedProductVisibilityProvider::class,
        ];
    }

    public function isVisibleProduct(Product|int|null $product): bool
    {
        $productId = $product instanceof Product ? $product->getId() : (int) $product;
        if (!$productId) {
            return false;
        }

        return $this->container
            ->get('oro_visibility.provider.resolved_product_visibility_provider')
            ->isVisible($productId);
    }
}

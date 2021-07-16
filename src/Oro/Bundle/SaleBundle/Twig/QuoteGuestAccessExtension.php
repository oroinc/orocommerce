<?php

namespace Oro\Bundle\SaleBundle\Twig;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\WebsiteBundle\Resolver\WebsiteUrlResolver;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to create a storefront link for accessing a quote by using its guest access identifier:
 *   - quote_guest_access_link
 */
class QuoteGuestAccessExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    const NAME = 'oro_sale_quote_guest_access';

    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('quote_guest_access_link', [$this, 'getGuestAccessLink'])
        ];
    }

    public function getGuestAccessLink(Quote $quote): ?string
    {
        if (!$quote->getWebsite() || !$this->getFeatureChecker()->isFeatureEnabled('guest_quote')) {
            return null;
        }

        return $this->getWebsiteUrlResolver()
            ->getWebsitePath(
                'oro_sale_quote_frontend_view_guest',
                ['guest_access_id' => $quote->getGuestAccessId()],
                $quote->getWebsite()
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    private function getFeatureChecker(): FeatureChecker
    {
        return $this->container->get(FeatureChecker::class);
    }

    private function getWebsiteUrlResolver(): WebsiteUrlResolver
    {
        return $this->container->get(WebsiteUrlResolver::class);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            FeatureChecker::class,
            WebsiteUrlResolver::class,
        ];
    }
}

<?php

namespace Oro\Bundle\SEOBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

class FullListUrlProvidersCompilerPass implements CompilerPassInterface
{
    const PROVIDER_REGISTRY = 'oro_seo.sitemap.provider.full_list_urls_provider_registry';
    const TAG_ALLOWED_URLS = 'oro_seo.sitemap.website_access_denied_urls_provider';
    const TAG = 'oro_seo.sitemap.url_items_provider';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::PROVIDER_REGISTRY)) {
            return;
        }

        $providers = $this->getProviders($container, self::TAG);
        $allowUrlsProviders = $this->getProviders($container, self::TAG_ALLOWED_URLS);
        $providers = array_merge($providers, $allowUrlsProviders);

        if (empty($providers)) {
            return;
        }

        $container
            ->getDefinition(self::PROVIDER_REGISTRY)
            ->replaceArgument(0, $providers);
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $tag
     *
     * @return array
     */
    private function getProviders(ContainerBuilder $container, $tag)
    {
        $taggedServices = $container->findTaggedServiceIds($tag);
        if (empty($taggedServices)) {
            return [];
        }

        $providers = [];
        foreach ($taggedServices as $serviceId => $tags) {
            foreach ($tags as $tagAttrs) {
                if (!isset($tagAttrs['alias'])) {
                    throw new LogicException(sprintf('Could not retrieve "alias" attribute for "%s"', $serviceId));
                }
                $providers[$tagAttrs['alias']] = new Reference($serviceId);
            }
        }

        return $providers;
    }
}

<?php

namespace Oro\Bundle\WebCatalogBundle\Twig;

use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to render web catalog content tree:
 *   - oro_web_catalog_tree
 *   - oro_web_catalog_content_variant_title
 */
class WebCatalogExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_web_catalog_tree', [$this, 'getNodesTree']),
            new TwigFunction('oro_web_catalog_content_variant_title', [$this, 'getContentVariantTitle']),
        ];
    }

    public function getNodesTree(WebCatalog $webCatalog): array
    {
        $treeHandler = $this->getTreeHandler();

        return $treeHandler->createTree($treeHandler->getTreeRootByWebCatalog($webCatalog));
    }

    /**
     * @param string $typeName
     * @return string
     */
    public function getContentVariantTitle($typeName)
    {
        return $this->getContentVariantTypeRegistry()->getContentVariantType($typeName)->getTitle();
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ContentNodeTreeHandler::class,
            ContentVariantTypeRegistry::class
        ];
    }

    private function getTreeHandler(): ContentNodeTreeHandler
    {
        return $this->container->get(ContentNodeTreeHandler::class);
    }

    private function getContentVariantTypeRegistry(): ContentVariantTypeRegistry
    {
        return $this->container->get(ContentVariantTypeRegistry::class);
    }
}

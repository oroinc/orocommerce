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
    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return ContentNodeTreeHandler
     */
    protected function getTreeHandler()
    {
        return $this->container->get(ContentNodeTreeHandler::class);
    }

    /**
     * @return ContentVariantTypeRegistry
     */
    protected function getContentVariantTypeRegistry()
    {
        return $this->container->get(ContentVariantTypeRegistry::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_web_catalog_tree', [$this, 'getNodesTree']),
            new TwigFunction('oro_web_catalog_content_variant_title', [$this, 'getContentVariantTitle']),
        ];
    }

    /**
     * @param WebCatalog $webCatalog
     * @return array
     */
    public function getNodesTree(WebCatalog $webCatalog)
    {
        $treeHandler = $this->getTreeHandler();
        $root = $treeHandler->getTreeRootByWebCatalog($webCatalog);

        return $treeHandler->createTree($root, true);
    }

    /**
     * @param string $typeName
     * @return string
     */
    public function getContentVariantTitle($typeName)
    {
        $type = $this->getContentVariantTypeRegistry()->getContentVariantType($typeName);

        return $type->getTitle();
    }

    public static function getSubscribedServices()
    {
        return [
            ContentNodeTreeHandler::class,
            ContentVariantTypeRegistry::class
        ];
    }
}

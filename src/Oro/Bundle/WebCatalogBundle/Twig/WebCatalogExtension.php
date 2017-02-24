<?php

namespace Oro\Bundle\WebCatalogBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler;

class WebCatalogExtension extends \Twig_Extension
{
    const NAME = 'oro_web_catalog_extension';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return ContentNodeTreeHandler
     */
    protected function getTreeHandler()
    {
        return $this->container->get('oro_web_catalog.content_node_tree_handler');
    }

    /**
     * @return ContentVariantTypeRegistry
     */
    protected function getContentVariantTypeRegistry()
    {
        return $this->container->get('oro_web_catalog.content_variant_type.registry');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_web_catalog_tree', [$this, 'getNodesTree']),
            new \Twig_SimpleFunction('oro_web_catalog_content_variant_title', [$this, 'getContentVariantTitle']),
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
}

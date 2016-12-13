<?php

namespace Oro\Bundle\WebCatalogBundle\Twig;

use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler;

class WebCatalogExtension extends \Twig_Extension
{
    const NAME = 'oro_web_catalog_extension';

    /**
     * @var ContentNodeTreeHandler
     */
    protected $treeHandler;

    /**
     * @var ContentVariantTypeRegistry
     */
    protected $contentVariantTypeRegistry;

    /**
     * @param ContentNodeTreeHandler $treeHandler
     * @param ContentVariantTypeRegistry $contentVariantTypeRegistry
     */
    public function __construct(
        ContentNodeTreeHandler $treeHandler,
        ContentVariantTypeRegistry $contentVariantTypeRegistry
    ) {
        $this->treeHandler = $treeHandler;
        $this->contentVariantTypeRegistry = $contentVariantTypeRegistry;
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
        $root = $this->treeHandler->getTreeRootByWebCatalog($webCatalog);

        return $this->treeHandler->createTree($root, true);
    }

    /**
     * @param string $typeName
     * @return string
     */
    public function getContentVariantTitle($typeName)
    {
        $type = $this->contentVariantTypeRegistry->getContentVariantType($typeName);

        return $type->getTitle();
    }
}

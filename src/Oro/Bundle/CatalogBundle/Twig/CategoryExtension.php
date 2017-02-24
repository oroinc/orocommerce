<?php

namespace Oro\Bundle\CatalogBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\CatalogBundle\JsTree\CategoryTreeHandler;

class CategoryExtension extends \Twig_Extension
{
    const NAME = 'oro_catalog_category_extension';

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
     * @return CategoryTreeHandler
     */
    protected function getCategoryTreeHandler()
    {
        return $this->container->get('oro_catalog.category_tree_handler');
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
            new \Twig_SimpleFunction('oro_category_list', [$this, 'getCategoryList']),
        ];
    }

    /**
     * @param string|null $rootLabel
     *
     * @return array
     */
    public function getCategoryList($rootLabel = null)
    {
        $tree = $this->getCategoryTreeHandler()->createTree();
        if ($rootLabel && array_key_exists(0, $tree)) {
            $tree[0]['text'] = $rootLabel;
        }

        return $tree;
    }
}

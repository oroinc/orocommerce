<?php

namespace Oro\Bundle\CatalogBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class CategoryExtension extends \Twig_Extension implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    const NAME = 'oro_catalog_category_extension';

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
            new \Twig_SimpleFunction(
                'oro_category_list',
                function ($rootLabel = null) {
                    $tree = $this->container->get('oro_catalog.category_tree_handler')->createTree();
                    if ($rootLabel && array_key_exists(0, $tree)) {
                        $tree[0]['text'] = $rootLabel;
                    }

                    return $tree;
                }
            ),
        ];
    }
}

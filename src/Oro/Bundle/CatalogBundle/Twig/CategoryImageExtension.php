<?php

namespace Oro\Bundle\CatalogBundle\Twig;

use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to get an image placeholder for a category:
 *   - category_image_placeholder
 */
class CategoryImageExtension extends AbstractExtension
{
    /** @var ContainerInterface */
    private $container;

    /** @var ImagePlaceholderProviderInterface */
    private $imagePlaceholderProvider;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('category_image_placeholder', [$this, 'getCategoryImagePlaceholder'])
        ];
    }

    /**
     * @param string $filter
     * @return string
     */
    public function getCategoryImagePlaceholder(string $filter): string
    {
        if (!$this->imagePlaceholderProvider) {
            $this->imagePlaceholderProvider = $this->container->get('oro_catalog.provider.category_image_placeholder');
        }

        return $this->imagePlaceholderProvider->getPath($filter);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_catalog_category_image_extension';
    }
}

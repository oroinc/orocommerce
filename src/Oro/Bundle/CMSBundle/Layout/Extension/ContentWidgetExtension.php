<?php

namespace Oro\Bundle\CMSBundle\Layout\Extension;

use Oro\Component\Layout\ContextAwareInterface;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Extension\AbstractExtension;
use Oro\Component\Layout\Extension\Theme\Model\DependencyInitializer;
use Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface;
use Oro\Component\Layout\Extension\Theme\ResourceProvider\ResourceProviderInterface;
use Oro\Component\Layout\Extension\Theme\Visitor\VisitorInterface;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\Loader\Generator\ElementDependentLayoutUpdateInterface;
use Oro\Component\Layout\Loader\LayoutUpdateLoaderInterface;

/**
 * Provides list of layout updates available for current context.
 */
class ContentWidgetExtension extends AbstractExtension
{
    /** @var string */
    private const EXTENSION_KEY = 'content_widget_type';

    /** @var LayoutUpdateLoaderInterface */
    protected $loader;

    /** @var DependencyInitializer */
    protected $dependencyInitializer;

    /** @var PathProviderInterface */
    protected $pathProvider;

    /** @var ResourceProviderInterface */
    protected $resourceProvider;

    /** @var VisitorInterface[] */
    protected $visitors = [];

    /** @var array */
    protected $updates = [];

    /**
     * @param LayoutUpdateLoaderInterface $loader
     * @param DependencyInitializer $dependencyInitializer
     * @param PathProviderInterface $pathProvider
     * @param ResourceProviderInterface $resourceProvider
     */
    public function __construct(
        LayoutUpdateLoaderInterface $loader,
        DependencyInitializer $dependencyInitializer,
        PathProviderInterface $pathProvider,
        ResourceProviderInterface $resourceProvider
    ) {
        $this->loader = $loader;
        $this->dependencyInitializer = $dependencyInitializer;
        $this->pathProvider = $pathProvider;
        $this->resourceProvider = $resourceProvider;
    }

    /**
     * @param VisitorInterface $visitor
     */
    public function addVisitor(VisitorInterface $visitor)
    {
        $this->visitors[] = $visitor;
    }

    /**
     * {@inheritdoc}
     */
    public function hasLayoutUpdates(LayoutItemInterface $item)
    {
        if ($item->getContext()->getOr(self::EXTENSION_KEY)) {
            return parent::hasLayoutUpdates($item);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadLayoutUpdates(ContextInterface $context)
    {
        if ($context->getOr(self::EXTENSION_KEY)) {
            $paths = $this->getPaths($context);
            $files = $this->resourceProvider->findApplicableResources($paths);
            foreach ($files as $file) {
                $this->loadLayoutUpdate($file);
            }
        }

        foreach ($this->visitors as $visitor) {
            $visitor->walkUpdates($this->updates, $context);
        }

        return $this->updates;
    }

    /**
     * @param string $file
     */
    protected function loadLayoutUpdate($file)
    {
        $update = $this->loader->load($file);
        if ($update) {
            $el = $update instanceof ElementDependentLayoutUpdateInterface
                ? $update->getElement()
                : 'content_widget_root';

            $this->updates[$el][] = $update;

            $this->dependencyInitializer->initialize($update);
        }
    }

    /**
     * Return paths that comes from provider and returns array of resource files
     *
     * @param ContextInterface $context
     *
     * @return array
     */
    protected function getPaths(ContextInterface $context)
    {
        if ($this->pathProvider instanceof ContextAwareInterface) {
            $this->pathProvider->setContext($context);
        }

        return $this->pathProvider->getPaths([]);
    }
}

<?php

namespace Oro\Bundle\CMSBundle\Layout\Extension;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Component\Layout\ContextAwareInterface;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Exception\UnexpectedTypeException;
use Oro\Component\Layout\Extension\AbstractExtension;
use Oro\Component\Layout\Extension\Theme\Model\DependencyInitializer;
use Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface;
use Oro\Component\Layout\Extension\Theme\ResourceProvider\ResourceProviderInterface;
use Oro\Component\Layout\Extension\Theme\Visitor\VisitorInterface;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutUpdateInterface;
use Oro\Component\Layout\Loader\Generator\ElementDependentLayoutUpdateInterface;
use Oro\Component\Layout\Loader\LayoutUpdateLoaderInterface;

/**
 * Provides list of layout updates available for current context.
 */
class ContentWidgetExtension extends AbstractExtension
{
    /** @var string */
    private const EXTENSION_KEY = 'content_widget';

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
     * The layout updates provided by this extension
     *
     * @var array of LayoutUpdateInterface[]
     *
     * Example:
     *  [
     *      'item_1' => array of LayoutUpdateInterface,
     *      'item_2' => array of LayoutUpdateInterface
     *  ]
     */
    private $layoutUpdates;

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

    public function addVisitor(VisitorInterface $visitor)
    {
        $this->visitors[] = $visitor;
    }

    /**
     * {@inheritdoc}
     */
    public function hasLayoutUpdates(LayoutItemInterface $item)
    {
        return !empty($this->getLayoutUpdates($item));
    }

    /**
     * {@inheritdoc}
     */
    public function getLayoutUpdates(LayoutItemInterface $item)
    {
        $contentWidget = $item->getContext()->getOr(self::EXTENSION_KEY);
        if (!$contentWidget instanceof ContentWidget) {
            return [];
        }

        $widgetType = $contentWidget->getWidgetType();
        if (!$widgetType) {
            return [];
        }

        if (!isset($this->layoutUpdates[$widgetType])) {
            $this->layoutUpdates[$widgetType] = $this->initLayoutUpdates($item->getContext());
        }

        $idOrAlias = $item->getAlias() ? : $item->getId();

        return $this->layoutUpdates[$widgetType][$idOrAlias] ?? [];
    }

    /**
     * @throws UnexpectedTypeException if any registered layout update is not an instance of LayoutUpdateInterface
     *                                 or layout item id is not a string
     */
    private function initLayoutUpdates(ContextInterface $context): array
    {
        $loadedLayoutUpdates = $this->loadLayoutUpdates($context);
        foreach ($loadedLayoutUpdates as $id => $updates) {
            if (!is_string($id)) {
                throw new UnexpectedTypeException($id, 'string', 'layout item id');
            }

            if (!is_array($updates)) {
                throw new UnexpectedTypeException($updates, 'array', sprintf('layout updates for item "%s"', $id));
            }

            foreach ($updates as $update) {
                if (!$update instanceof LayoutUpdateInterface) {
                    throw new UnexpectedTypeException($update, LayoutUpdateInterface::class);
                }
            }
        }

        return $loadedLayoutUpdates;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadLayoutUpdates(ContextInterface $context)
    {
        if ($context->getOr(self::EXTENSION_KEY)) {
            $paths = $this->getPaths($context);
            $files = $this->resourceProvider->findApplicableResources($paths);

            $this->updates = [];
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

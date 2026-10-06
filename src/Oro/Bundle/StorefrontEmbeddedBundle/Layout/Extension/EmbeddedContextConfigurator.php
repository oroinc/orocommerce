<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Layout\Extension;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adds the `embedded` flag to the layout context: true only while an embedded fragment is rendered (the fragment
 * renderer sets the REQUEST_ATTRIBUTE on its sub-request). The bundle's embedded layout updates gate on it
 * (`conditions: context["embedded"]`), so they apply to the fragment only and never touch Oro's own pages or the
 * subtree/datagrid AJAX those pages make. Scoped to the embeddable routes.
 */
class EmbeddedContextConfigurator implements ContextConfiguratorInterface
{
    public const string REQUEST_ATTRIBUTE = '_embedded_fragment';

    /**
     * @var string[]
     */
    private ?array $routes = null;

    /**
     * @param array<string, string> $embeddedBlocks embedded block id => route name
     */
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly array $embeddedBlocks,
    ) {
    }

    #[\Override]
    public function configureContext(ContextInterface $context): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request || !$this->rendersEmbeddableRoute($request, $context)) {
            return;
        }

        $context->getResolver()
            ->define('embedded')
            ->allowedTypes('bool')
            ->default((bool)$request->attributes->get(self::REQUEST_ATTRIBUTE, false));
    }

    private function rendersEmbeddableRoute(Request $request, ContextInterface $context): bool
    {
        $renderedRoutes = array_filter([
            $request->attributes->get('_route'),
            $context->getOr('action'),
        ]);

        return (bool)array_intersect($renderedRoutes, $this->getRoutes());
    }

    private function getRoutes(): array
    {
        return $this->routes ??= array_values(array_unique($this->embeddedBlocks));
    }
}

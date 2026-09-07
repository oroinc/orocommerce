<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\ActionContextConfigurator;
use Oro\Bundle\StorefrontEmbeddedBundle\Layout\Extension\EmbeddedContextConfigurator;
use Oro\Component\Layout\LayoutContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EmbeddedContextConfiguratorTest extends TestCase
{
    private const string PROFILE_ROUTE = 'oro_customer_frontend_customer_user_profile';
    private const string SHOPPING_LIST_ROUTE = 'oro_shopping_list_frontend_update';
    private const string FRAGMENT_ROUTE = 'oro_storefront_embedded_fragment';

    private RequestStack $requestStack;
    private EmbeddedContextConfigurator $configurator;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->configurator = new EmbeddedContextConfigurator(
            $this->requestStack,
            [
                'embedded_shopping_list' => self::SHOPPING_LIST_ROUTE,
                'embedded_profile' => self::PROFILE_ROUTE
            ]
        );
    }

    private function pushRequest(string $route, array $attributes = []): void
    {
        $request = Request::create('');
        $request->attributes->set('_route', $route);
        foreach ($attributes as $name => $value) {
            $request->attributes->set($name, $value);
        }

        $this->requestStack->push($request);
    }

    public function testEmbeddedOptionIsNotDefinedWithoutCurrentRequest(): void
    {
        $context = new LayoutContext();

        $this->configurator->configureContext($context);
        $context->resolve();

        self::assertFalse($context->has('embedded'));
    }

    public function testEmbeddedOptionIsNotDefinedOnNotEmbeddableRouteEvenWithEmbeddedFragmentAttribute(): void
    {
        $this->pushRequest(
            'oro_product_frontend_product_view',
            [EmbeddedContextConfigurator::REQUEST_ATTRIBUTE => true]
        );
        $context = new LayoutContext();

        $this->configurator->configureContext($context);
        $context->resolve();

        self::assertFalse($context->has('embedded'));
    }

    public function testEmbeddedOptionDefaultsToFalseWithoutEmbeddedFragmentAttribute(): void
    {
        $this->pushRequest(self::PROFILE_ROUTE);
        $context = new LayoutContext();

        $this->configurator->configureContext($context);
        $context->resolve();

        self::assertTrue($context->has('embedded'));
        self::assertFalse($context->get('embedded'));
    }

    public function testEmbeddedOptionDefaultsToTrueWithEmbeddedFragmentAttribute(): void
    {
        $this->pushRequest(self::PROFILE_ROUTE, [EmbeddedContextConfigurator::REQUEST_ATTRIBUTE => true]);
        $context = new LayoutContext();

        $this->configurator->configureContext($context);
        $context->resolve();

        self::assertTrue($context->get('embedded'));
    }

    public function testEmbeddedOptionIsDefinedWhenAnotherRouteRendersTheLayoutOfAnEmbeddableRoute(): void
    {
        $this->pushRequest('oro_order_frontend_print');
        $context = new LayoutContext();
        $context->set('action', self::PROFILE_ROUTE);

        $this->configurator->configureContext($context);
        (new ActionContextConfigurator())->configureContext($context);
        $context->resolve();

        self::assertFalse($context->get('embedded'));
    }

    public function testEmbeddedOptionIsResolvedFromForwardedSubRequestAndNotFromMainRequest(): void
    {
        $this->pushRequest(self::FRAGMENT_ROUTE);
        $this->pushRequest(self::SHOPPING_LIST_ROUTE, [EmbeddedContextConfigurator::REQUEST_ATTRIBUTE => true]);
        $context = new LayoutContext();

        $this->configurator->configureContext($context);
        $context->resolve();

        self::assertTrue($context->get('embedded'));
    }
}

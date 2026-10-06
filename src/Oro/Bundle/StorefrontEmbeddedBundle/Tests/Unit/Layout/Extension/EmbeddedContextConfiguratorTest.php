<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\ActionContextConfigurator;
use Oro\Bundle\StorefrontEmbeddedBundle\Layout\Extension\EmbeddedContextConfigurator;
use Oro\Component\Layout\LayoutContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class EmbeddedContextConfiguratorTest extends TestCase
{
    private RequestStack $requestStack;
    private EmbeddedContextConfigurator $configurator;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->configurator = new EmbeddedContextConfigurator(
            $this->requestStack,
            [
                'embedded_shopping_list' => 'oro_shopping_list_frontend_update',
                'embedded_profile' => 'oro_customer_frontend_customer_user_profile'
            ]
        );
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
        $request = Request::create('');
        $request->attributes->set('_route', 'oro_product_frontend_product_view');
        $request->attributes->set(EmbeddedContextConfigurator::REQUEST_ATTRIBUTE, true);
        $this->requestStack->push($request);
        $context = new LayoutContext();

        $this->configurator->configureContext($context);
        $context->resolve();

        self::assertFalse($context->has('embedded'));
    }

    public function testEmbeddedOptionDefaultsToFalseWithoutEmbeddedFragmentAttribute(): void
    {
        $request = Request::create('');
        $request->attributes->set('_route', 'oro_customer_frontend_customer_user_profile');
        $this->requestStack->push($request);
        $context = new LayoutContext();

        $this->configurator->configureContext($context);
        $context->resolve();

        self::assertFalse($context->get('embedded'));
    }

    public function testEmbeddedOptionDefaultsToTrueWithEmbeddedFragmentAttribute(): void
    {
        $request = Request::create('');
        $request->attributes->set('_route', 'oro_customer_frontend_customer_user_profile');
        $request->attributes->set(EmbeddedContextConfigurator::REQUEST_ATTRIBUTE, true);
        $this->requestStack->push($request);
        $context = new LayoutContext();

        $this->configurator->configureContext($context);
        $context->resolve();

        self::assertTrue($context->get('embedded'));
    }

    public function testEmbeddedOptionIsDefinedWhenAnotherRouteRendersTheLayoutOfAnEmbeddableRoute(): void
    {
        $request = Request::create('');
        $request->attributes->set('_route', 'oro_order_frontend_print');
        $this->requestStack->push($request);
        $context = new LayoutContext();
        $context->set('action', 'oro_customer_frontend_customer_user_profile');

        $this->configurator->configureContext($context);
        (new ActionContextConfigurator())->configureContext($context);
        $context->resolve();

        self::assertFalse($context->get('embedded'));
    }

    public function testEmbeddedOptionIsResolvedFromForwardedSubRequestAndNotFromMainRequest(): void
    {
        $mainRequest = Request::create('');
        $mainRequest->attributes->set('_route', 'oro_storefront_embedded_fragment');
        $this->requestStack->push($mainRequest);
        $subRequest = Request::create('');
        $subRequest->attributes->set('_route', 'oro_shopping_list_frontend_update');
        $subRequest->attributes->set(EmbeddedContextConfigurator::REQUEST_ATTRIBUTE, true);
        $this->requestStack->push($subRequest);
        $context = new LayoutContext();

        $this->configurator->configureContext($context);
        $context->resolve();

        self::assertTrue($context->get('embedded'));
    }
}

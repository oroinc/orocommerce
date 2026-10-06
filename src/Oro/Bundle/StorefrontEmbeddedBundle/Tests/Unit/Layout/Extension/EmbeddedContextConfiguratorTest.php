<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\StorefrontEmbeddedBundle\Layout\Extension\EmbeddedContextConfigurator;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
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
            new EmbeddedRequestChecker($this->requestStack)
        );
    }

    public function testIsEmbeddedFragmentOptionIsNotDefinedWithoutCurrentRequest(): void
    {
        $context = new LayoutContext();

        $this->configurator->configureContext($context);
        $context->resolve();

        self::assertFalse($context->has('is_embedded_fragment'));
    }

    public function testIsEmbeddedFragmentOptionIsDefinedAndFalseWithoutFragmentAttribute(): void
    {
        $request = Request::create('');
        $this->requestStack->push($request);
        $context = new LayoutContext();

        $this->configurator->configureContext($context);
        $context->resolve();

        self::assertTrue($context->has('is_embedded_fragment'));
        self::assertFalse($context->get('is_embedded_fragment'));
    }

    public function testIsEmbeddedFragmentOptionDefaultsToTrueWithEmbeddedFragmentAttribute(): void
    {
        $request = Request::create('');
        $request->attributes->set(EmbeddedRequestChecker::EMBEDDED_FRAGMENT_NAME, 'embedded_profile');
        $this->requestStack->push($request);
        $context = new LayoutContext();

        $this->configurator->configureContext($context);
        $context->resolve();

        self::assertTrue($context->get('is_embedded_fragment'));
    }

    public function testIsEmbeddedFragmentOptionIsResolvedFromCurrentSubRequestAndNotFromMainRequest(): void
    {
        $mainRequest = Request::create('');
        $mainRequest->attributes->set(EmbeddedRequestChecker::EMBEDDED_FRAGMENT_NAME, 'embedded_shopping_list');
        $this->requestStack->push($mainRequest);
        $this->requestStack->push(Request::create(''));
        $context = new LayoutContext();

        $this->configurator->configureContext($context);
        $context->resolve();

        self::assertFalse($context->get('is_embedded_fragment'));
    }
}

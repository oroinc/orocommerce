<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Request;

use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class EmbeddedRequestCheckerTest extends TestCase
{
    private RequestStack $requestStack;
    private EmbeddedRequestChecker $checker;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->checker = new EmbeddedRequestChecker($this->requestStack);
    }

    public function testIsNotEmbeddedWhenThereIsNoMainRequest(): void
    {
        self::assertFalse($this->checker->isEmbeddedRequest());
    }

    public function testIsNotEmbeddedWhenTheHeaderIsMissing(): void
    {
        $this->requestStack->push(Request::create('/embedded/fragment/embedded_profile'));

        self::assertFalse($this->checker->isEmbeddedRequest());
    }

    public function testIsEmbeddedWhenTheMainRequestHasTheHeader(): void
    {
        $request = Request::create('/customer/shoppinglist');
        $request->headers->set(EmbeddedRequestChecker::EMBEDDED_HEADER, '1');
        $this->requestStack->push($request);

        self::assertTrue($this->checker->isEmbeddedRequest());
    }

    public function testIsEmbeddedWhileRenderingASubRequestOfAnEmbeddedRequest(): void
    {
        $mainRequest = Request::create('/embedded/fragment/embedded_profile');
        $mainRequest->headers->set(EmbeddedRequestChecker::EMBEDDED_HEADER, '1');
        $this->requestStack->push($mainRequest);
        $this->requestStack->push(Request::create('/customer/profile'));

        self::assertTrue($this->checker->isEmbeddedRequest());
    }

    public function testIsNotEmbeddedWhenOnlyTheSubRequestCarriesTheHeader(): void
    {
        $this->requestStack->push(Request::create('/customer/profile'));
        $subRequest = Request::create('/embedded/fragment/embedded_profile');
        $subRequest->headers->set(EmbeddedRequestChecker::EMBEDDED_HEADER, '1');
        $this->requestStack->push($subRequest);

        self::assertFalse($this->checker->isEmbeddedRequest());
    }

    public function testIsNotEmbeddedReloadWhenThereIsNoMainRequest(): void
    {
        self::assertFalse($this->checker->isEmbeddedRequestReload());
    }

    public function testIsNotEmbeddedReloadWhenTheHashNavigationHeaderIsMissing(): void
    {
        $this->requestStack->push(Request::create('/embedded/fragment/embedded_profile'));

        self::assertFalse($this->checker->isEmbeddedRequestReload());
    }

    /**
     * @dataProvider isEmbeddedReloadDependsOnTheHashNavigationHeaderValueProvider
     */
    public function testIsEmbeddedReloadDependsOnTheHashNavigationHeaderValue(
        string $headerValue,
        bool $expectedReload
    ): void {
        $request = Request::create('/embedded/fragment/embedded_profile');
        $request->headers->set(ResponseHashnavListener::HASH_NAVIGATION_HEADER, $headerValue);
        $this->requestStack->push($request);

        self::assertSame($expectedReload, $this->checker->isEmbeddedRequestReload());
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function isEmbeddedReloadDependsOnTheHashNavigationHeaderValueProvider(): iterable
    {
        yield 'truthy value' => ['1', true];
        yield 'zero value' => ['0', false];
    }

    public function testIsNotEmbeddedReloadWhenOnlyTheSubRequestCarriesTheHashNavigationHeader(): void
    {
        $this->requestStack->push(Request::create('/embedded/fragment/embedded_profile'));
        $subRequest = Request::create('/customer/profile');
        $subRequest->headers->set(ResponseHashnavListener::HASH_NAVIGATION_HEADER, '1');
        $this->requestStack->push($subRequest);

        self::assertFalse($this->checker->isEmbeddedRequestReload());
    }
}

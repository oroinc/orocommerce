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

    public function testIsNotEmbeddedWithoutMainRequest(): void
    {
        self::assertFalse($this->checker->isEmbeddedRequest());
    }

    public function testIsNotEmbeddedWhenHeaderIsMissing(): void
    {
        $this->requestStack->push(Request::create('/embedded/fragment/embedded_profile'));

        self::assertFalse($this->checker->isEmbeddedRequest());
    }

    public function testIsEmbeddedWhenMainRequestHasHeader(): void
    {
        $request = Request::create('/customer/shoppinglist');
        $request->headers->set(EmbeddedRequestChecker::EMBEDDED_HEADER, '1');
        $this->requestStack->push($request);

        self::assertTrue($this->checker->isEmbeddedRequest());
    }

    public function testIsEmbeddedWhileRenderingSubRequestOfEmbeddedRequest(): void
    {
        $mainRequest = Request::create('/embedded/fragment/embedded_profile');
        $mainRequest->headers->set(EmbeddedRequestChecker::EMBEDDED_HEADER, '1');
        $this->requestStack->push($mainRequest);
        $this->requestStack->push(Request::create('/customer/profile'));

        self::assertTrue($this->checker->isEmbeddedRequest());
    }

    public function testIsEmbeddedWhenGivenRequestHasHeaderAndMainRequestDoesNot(): void
    {
        $this->requestStack->push(Request::create('/customer/profile'));
        $request = Request::create('/embedded/fragment/embedded_profile');
        $request->headers->set(EmbeddedRequestChecker::EMBEDDED_HEADER, '1');

        self::assertTrue($this->checker->isEmbeddedRequest($request));
    }

    public function testIsNotEmbeddedWhenGivenRequestHasNoHeaderAndMainRequestHasIt(): void
    {
        $mainRequest = Request::create('/embedded/fragment/embedded_profile');
        $mainRequest->headers->set(EmbeddedRequestChecker::EMBEDDED_HEADER, '1');
        $this->requestStack->push($mainRequest);

        self::assertFalse($this->checker->isEmbeddedRequest(Request::create('/customer/profile')));
    }

    public function testIsNotEmbeddedReloadWithoutMainRequest(): void
    {
        self::assertFalse($this->checker->isEmbeddedRequestReload());
    }

    public function testIsNotEmbeddedReloadWhenHashNavigationHeaderIsMissing(): void
    {
        $this->requestStack->push(Request::create('/embedded/fragment/embedded_profile'));

        self::assertFalse($this->checker->isEmbeddedRequestReload());
    }

    /**
     * @dataProvider isEmbeddedReloadDependsOnHashNavigationHeaderValueProvider
     */
    public function testIsEmbeddedReloadDependsOnHashNavigationHeaderValue(
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
    public static function isEmbeddedReloadDependsOnHashNavigationHeaderValueProvider(): iterable
    {
        yield 'truthy value' => ['1', true];
        yield 'zero value' => ['0', false];
    }

    public function testIsEmbeddedReloadWhileRenderingSubRequestOfReloadedRequest(): void
    {
        $mainRequest = Request::create('/embedded/fragment/embedded_profile');
        $mainRequest->headers->set(ResponseHashnavListener::HASH_NAVIGATION_HEADER, '1');
        $this->requestStack->push($mainRequest);
        $this->requestStack->push(Request::create('/customer/profile'));

        self::assertTrue($this->checker->isEmbeddedRequestReload());
    }

    public function testIsEmbeddedReloadWhenGivenRequestHasHashNavigationHeaderAndMainRequestDoesNot(): void
    {
        $this->requestStack->push(Request::create('/customer/profile'));
        $request = Request::create('/embedded/fragment/embedded_profile');
        $request->headers->set(ResponseHashnavListener::HASH_NAVIGATION_HEADER, '1');

        self::assertTrue($this->checker->isEmbeddedRequestReload($request));
    }

    public function testIsNotEmbeddedReloadWhenGivenRequestHasNoHashNavigationHeaderAndMainRequestHasIt(): void
    {
        $mainRequest = Request::create('/embedded/fragment/embedded_profile');
        $mainRequest->headers->set(ResponseHashnavListener::HASH_NAVIGATION_HEADER, '1');
        $this->requestStack->push($mainRequest);

        self::assertFalse($this->checker->isEmbeddedRequestReload(Request::create('/customer/profile')));
    }

    public function testIsNotEmbeddedFragmentWhenMainRequestHasNoFragmentName(): void
    {
        $this->requestStack->push(Request::create('/customer/profile'));

        self::assertFalse($this->checker->isEmbeddedFragmentRequest());
    }

    public function testIsEmbeddedFragmentWhenMainRequestHasFragmentName(): void
    {
        $request = Request::create('/customer/profile');
        $request->attributes->set(EmbeddedRequestChecker::EMBEDDED_FRAGMENT_NAME, 'embedded_profile');
        $this->requestStack->push($request);

        self::assertTrue($this->checker->isEmbeddedFragmentRequest());
    }

    public function testReturnsNoFragmentNameWithoutMainRequest(): void
    {
        self::assertNull($this->checker->getEmbeddedFragmentName());
    }

    public function testReturnsNoFragmentNameWhenMainRequestHasNoFragmentName(): void
    {
        $this->requestStack->push(Request::create('/customer/profile'));

        self::assertNull($this->checker->getEmbeddedFragmentName());
    }

    public function testReturnsFragmentNameOfMainRequest(): void
    {
        $request = Request::create('/customer/profile');
        $request->attributes->set(EmbeddedRequestChecker::EMBEDDED_FRAGMENT_NAME, 'embedded_profile');
        $this->requestStack->push($request);

        self::assertSame('embedded_profile', $this->checker->getEmbeddedFragmentName());
    }

    public function testReturnsFragmentNameOfGivenRequestInsteadOfMainRequest(): void
    {
        $mainRequest = Request::create('/customer/profile');
        $mainRequest->attributes->set(EmbeddedRequestChecker::EMBEDDED_FRAGMENT_NAME, 'embedded_profile');
        $this->requestStack->push($mainRequest);
        $request = Request::create('/customer/shoppinglist');
        $request->attributes->set(EmbeddedRequestChecker::EMBEDDED_FRAGMENT_NAME, 'embedded_shopping_lists');

        self::assertSame('embedded_shopping_lists', $this->checker->getEmbeddedFragmentName($request));
    }

    public function testReturnsNoFragmentNameWhenGivenRequestHasNoFragmentNameAndMainRequestHasIt(): void
    {
        $mainRequest = Request::create('/customer/profile');
        $mainRequest->attributes->set(EmbeddedRequestChecker::EMBEDDED_FRAGMENT_NAME, 'embedded_profile');
        $this->requestStack->push($mainRequest);

        self::assertNull($this->checker->getEmbeddedFragmentName(Request::create('/customer/profile')));
    }

    public function testReturnsNoFragmentNameWhenFragmentNameIsNotString(): void
    {
        $request = Request::create('/customer/profile');
        $request->attributes->set(EmbeddedRequestChecker::EMBEDDED_FRAGMENT_NAME, ['embedded_profile']);

        self::assertNull($this->checker->getEmbeddedFragmentName($request));
    }
}

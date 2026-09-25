<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Request;

use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EmbeddedRequestCheckerTest extends TestCase
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
        $this->requestStack->push(Request::create(''));

        self::assertFalse($this->checker->isEmbeddedRequest());
    }

    public function testIsEmbeddedWhenTheMainRequestCarriesTheHeader(): void
    {
        $this->requestStack->push($this->createEmbeddedRequest());

        self::assertTrue($this->checker->isEmbeddedRequest());
    }

    public function testIsEmbeddedWhileRenderingASubRequestOfAnEmbeddedRequest(): void
    {
        $this->requestStack->push($this->createEmbeddedRequest());
        $this->requestStack->push(Request::create('/customer/profile'));

        self::assertTrue($this->checker->isEmbeddedRequest());
    }

    public function testServesTheProxyPrefixTheHostNamed(): void
    {
        $this->requestStack->push($this->createEmbeddedRequest('/__oro'));

        self::assertSame('/__oro', $this->checker->getProxyPrefix());
    }

    public function testDropsTheTrailingSlashOfTheProxyPrefix(): void
    {
        $this->requestStack->push($this->createEmbeddedRequest('/__oro/'));

        self::assertSame('/__oro', $this->checker->getProxyPrefix());
    }

    public function testHasNoProxyPrefixWhenTheHostNamedNone(): void
    {
        $this->requestStack->push($this->createEmbeddedRequest());

        self::assertSame('', $this->checker->getProxyPrefix());
    }

    /**
     * @dataProvider notAProxyPrefixDataProvider
     */
    public function testRefusesAPrefixThatWouldSendTheBrowserSomewhereElse(string $header): void
    {
        $this->requestStack->push($this->createEmbeddedRequest($header));

        self::assertSame('', $this->checker->getProxyPrefix());
    }

    public function notAProxyPrefixDataProvider(): array
    {
        return [
            'another origin' => ['http://evil.example'],
            'protocol relative' => ['//evil.example'],
            'not a path' => ['__oro'],
            'root' => ['/'],
            'with a space' => ['/__oro /x'],
        ];
    }

    public function testServesTheAssetRootsTheHostSaidItServesItself(): void
    {
        $this->requestStack->push($this->createEmbeddedRequestServingAssetRoots('/build,/bundles'));

        self::assertSame(['/build', '/bundles'], $this->checker->getAssetRootsServedByHost());
    }

    public function testReadsAssetRootsAroundWhichTheHostLeftSpaceOrASlash(): void
    {
        $this->requestStack->push($this->createEmbeddedRequestServingAssetRoots('/build/, /bundles'));

        self::assertSame(['/build', '/bundles'], $this->checker->getAssetRootsServedByHost());
    }

    public function testServesNoAssetRootsWhenTheHostNamedNone(): void
    {
        $this->requestStack->push($this->createEmbeddedRequest());

        self::assertSame([], $this->checker->getAssetRootsServedByHost());
    }

    public function testDropsAnAssetRootThatWouldSendTheBrowserSomewhereElse(): void
    {
        $this->requestStack->push(
            $this->createEmbeddedRequestServingAssetRoots('http://evil.example,//evil.example,build,/,/bu ild,/build')
        );

        self::assertSame(['/build'], $this->checker->getAssetRootsServedByHost());
    }

    public function testKnowsWhichPathsTheHostSaidItServes(): void
    {
        $this->requestStack->push($this->createEmbeddedRequestServingAssetRoots('/build'));

        self::assertTrue($this->checker->isServedByHost('/build/intelligent_blue/embedded_app.js'));
        self::assertFalse($this->checker->isServedByHost('/bundles/oroui/img/info-icon.png'));
        self::assertFalse($this->checker->isServedByHost('/buildings/list'));
    }

    private function createEmbeddedRequestFor(string $route): Request
    {
        $request = $this->createEmbeddedRequest();
        $request->attributes->set('_route', $route);

        return $request;
    }

    private function createEmbeddedRequest(?string $proxyPrefix = null): Request
    {
        $request = Request::create('/embedded/fragment/embedded_profile');
        $request->headers->set(EmbeddedRequestChecker::EMBEDDED_HEADER, '1');
        if ($proxyPrefix !== null) {
            $request->headers->set(EmbeddedRequestChecker::PROXY_PREFIX_HEADER, $proxyPrefix);
        }

        return $request;
    }

    private function createEmbeddedRequestServingAssetRoots(string $assetRoots): Request
    {
        $request = $this->createEmbeddedRequest();
        $request->headers->set(EmbeddedRequestChecker::ASSET_ROOTS_HEADER, $assetRoots);

        return $request;
    }
}

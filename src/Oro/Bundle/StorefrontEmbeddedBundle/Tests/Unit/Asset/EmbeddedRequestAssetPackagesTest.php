<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Asset;

use Oro\Bundle\StorefrontEmbeddedBundle\Asset\EmbeddedRequestAssetPackages;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EmbeddedRequestAssetPackagesTest extends TestCase
{
    private Packages&MockObject $innerPackages;
    private EmbeddedRequestChecker&MockObject $embeddedRequestChecker;
    private EmbeddedRequestAssetPackages $packages;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerPackages = $this->createMock(Packages::class);
        $this->embeddedRequestChecker = $this->createMock(EmbeddedRequestChecker::class);

        $requestStack = new RequestStack();
        $requestStack->push(Request::create('http://oro.loc/embedded/fragment/embedded_profile'));

        $this->packages = new EmbeddedRequestAssetPackages(
            $this->innerPackages,
            $requestStack,
            $this->embeddedRequestChecker
        );
    }

    public function testServesABundledFileFromOroOnAnEmbeddedRequest(): void
    {
        $this->embedded(true);
        $this->innerUrlIs('/bundles/');

        self::assertSame('http://oro.loc/bundles/', $this->packages->getUrl('bundles/'));
    }

    public function testKeepsThePathOnOroOwnPages(): void
    {
        $this->embedded(false);
        $this->innerUrlIs('/bundles/');

        self::assertSame('/bundles/', $this->packages->getUrl('bundles/'));
    }

    public function testServesTheThemeBuildFromOroOnAnEmbeddedRequest(): void
    {
        $this->embedded(true);
        $this->innerUrlIs('/build/intelligent_blue/embedded_app.js?v=1');

        self::assertSame(
            'http://oro.loc/build/intelligent_blue/embedded_app.js?v=1',
            $this->packages->getUrl('build/intelligent_blue/embedded_app.js')
        );
    }

    public function testKeepsThePathOfARootTheHostSaidItServesItself(): void
    {
        $this->embedded(true);
        $this->hostServesAssetRoots(['/build']);
        $this->innerUrlIs('/build/intelligent_blue/embedded_app.js?v=1');

        self::assertSame(
            '/build/intelligent_blue/embedded_app.js?v=1',
            $this->packages->getUrl('build/intelligent_blue/embedded_app.js')
        );
    }

    public function testServesFromOroTheRootsTheHostDoesNotServe(): void
    {
        $this->embedded(true);
        $this->hostServesAssetRoots(['/build']);
        $this->innerUrlIs('/bundles/oroui/img/info-icon.png');

        self::assertSame(
            'http://oro.loc/bundles/oroui/img/info-icon.png',
            $this->packages->getUrl('bundles/oroui/img/info-icon.png')
        );
    }

    public function testLeavesAnAlreadyAbsoluteUrlAlone(): void
    {
        $this->embeddedRequestChecker->expects(self::never())
            ->method('isEmbeddedRequest');
        $this->innerUrlIs('https://cdn.example/bundles/logo.png');

        self::assertSame('https://cdn.example/bundles/logo.png', $this->packages->getUrl('bundles/logo.png'));
    }

    public function testLeavesAProtocolRelativeUrlAlone(): void
    {
        $this->embeddedRequestChecker->expects(self::never())
            ->method('isEmbeddedRequest');
        $this->innerUrlIs('//cdn.example/bundles/logo.png');

        self::assertSame('//cdn.example/bundles/logo.png', $this->packages->getUrl('bundles/logo.png'));
    }

    private function embedded(bool $isEmbeddedRequest): void
    {
        $this->embeddedRequestChecker->expects(self::once())
            ->method('isEmbeddedRequest')
            ->willReturn($isEmbeddedRequest);
    }

    private function hostServesAssetRoots(array $assetRoots): void
    {
        $this->embeddedRequestChecker->expects(self::once())
            ->method('isServedByHost')
            ->willReturnCallback(static function (string $path) use ($assetRoots): bool {
                foreach ($assetRoots as $root) {
                    if (str_starts_with($path, $root . '/')) {
                        return true;
                    }
                }

                return false;
            });
    }

    private function innerUrlIs(string $url): void
    {
        $this->innerPackages->expects(self::once())
            ->method('getUrl')
            ->willReturn($url);
    }
}

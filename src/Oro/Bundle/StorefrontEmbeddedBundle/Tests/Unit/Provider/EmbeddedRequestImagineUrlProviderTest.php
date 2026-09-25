<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Imagine\Provider\ImagineUrlProviderInterface;
use Oro\Bundle\StorefrontEmbeddedBundle\Provider\EmbeddedRequestImagineUrlProvider;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmbeddedRequestImagineUrlProviderTest extends TestCase
{
    private const string PLACEHOLDER = 'bundles/oroproduct/images/no_image.png';

    private ImagineUrlProviderInterface&MockObject $innerImagineUrlProvider;
    private EmbeddedRequestChecker&MockObject $embeddedRequestChecker;
    private EmbeddedRequestImagineUrlProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerImagineUrlProvider = $this->createMock(ImagineUrlProviderInterface::class);
        $this->embeddedRequestChecker = $this->createMock(EmbeddedRequestChecker::class);

        $this->provider = new EmbeddedRequestImagineUrlProvider(
            $this->innerImagineUrlProvider,
            $this->embeddedRequestChecker
        );
    }

    public function testAsksForAnAbsoluteUrlOnAnEmbeddedRequest(): void
    {
        $this->referenceTypeResolvedTo(UrlGeneratorInterface::ABSOLUTE_PATH, UrlGeneratorInterface::ABSOLUTE_URL);

        $this->innerImagineUrlProvider->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with(self::PLACEHOLDER, 'product_large', '', UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://oro.loc/media/cache/resolve/product_large/' . self::PLACEHOLDER);

        self::assertSame(
            'http://oro.loc/media/cache/resolve/product_large/' . self::PLACEHOLDER,
            $this->provider->getFilteredImageUrl(
                self::PLACEHOLDER,
                'product_large',
                '',
                UrlGeneratorInterface::ABSOLUTE_PATH
            )
        );
    }

    public function testLeavesThePathAloneOnOroOwnPages(): void
    {
        $this->referenceTypeResolvedTo(UrlGeneratorInterface::ABSOLUTE_PATH, UrlGeneratorInterface::ABSOLUTE_PATH);

        $this->innerImagineUrlProvider->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with(self::PLACEHOLDER, 'product_large', '', UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/media/cache/resolve/product_large/' . self::PLACEHOLDER);

        self::assertSame(
            '/media/cache/resolve/product_large/' . self::PLACEHOLDER,
            $this->provider->getFilteredImageUrl(
                self::PLACEHOLDER,
                'product_large',
                '',
                UrlGeneratorInterface::ABSOLUTE_PATH
            )
        );
    }

    public function testKeepsTheReferenceTypeTheCallerAskedFor(): void
    {
        $this->referenceTypeResolvedTo(UrlGeneratorInterface::ABSOLUTE_URL, UrlGeneratorInterface::ABSOLUTE_URL);

        $this->innerImagineUrlProvider->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with(self::PLACEHOLDER, 'product_large', '', UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://oro.loc/media/cache/resolve/product_large/' . self::PLACEHOLDER);

        self::assertSame(
            'http://oro.loc/media/cache/resolve/product_large/' . self::PLACEHOLDER,
            $this->provider->getFilteredImageUrl(self::PLACEHOLDER, 'product_large')
        );
    }

    private function referenceTypeResolvedTo(int $askedReferenceType, int $resolvedReferenceType): void
    {
        $this->embeddedRequestChecker->expects(self::once())
            ->method('resolveReferenceType')
            ->with($askedReferenceType)
            ->willReturn($resolvedReferenceType);
    }
}

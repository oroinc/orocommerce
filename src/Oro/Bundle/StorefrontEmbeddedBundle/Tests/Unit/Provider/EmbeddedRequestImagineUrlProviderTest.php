<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Imagine\Provider\ImagineUrlProviderInterface;
use Oro\Bundle\StorefrontEmbeddedBundle\Provider\EmbeddedRequestImagineUrlProvider;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class EmbeddedRequestImagineUrlProviderTest extends TestCase
{
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

    public function testGetFilteredImageUrlReturnsAbsoluteUrlForEmbeddedFragmentRequest(): void
    {
        $this->embeddedRequestChecker
            ->expects(self::once())
            ->method('isEmbeddedRequest')
            ->willReturn(true);

        $this->innerImagineUrlProvider
            ->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with('bundles/oroproduct/images/no_image.png', 'product_large', '', UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://oro.loc/media/cache/resolve/product_large/bundles/oroproduct/images/no_image.png');

        self::assertSame(
            'http://oro.loc/media/cache/resolve/product_large/bundles/oroproduct/images/no_image.png',
            $this->provider->getFilteredImageUrl(
                'bundles/oroproduct/images/no_image.png',
                'product_large',
                '',
                UrlGeneratorInterface::ABSOLUTE_PATH
            )
        );
    }

    public function testGetFilteredImageUrlReturnsPathOutsideEmbeddedFragmentRequest(): void
    {
        $this->embeddedRequestChecker
            ->expects(self::once())
            ->method('isEmbeddedRequest')
            ->willReturn(false);

        $this->innerImagineUrlProvider
            ->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with('bundles/oroproduct/images/no_image.png', 'product_large', '', UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/media/cache/resolve/product_large/bundles/oroproduct/images/no_image.png');

        self::assertSame(
            '/media/cache/resolve/product_large/bundles/oroproduct/images/no_image.png',
            $this->provider->getFilteredImageUrl(
                'bundles/oroproduct/images/no_image.png',
                'product_large',
                '',
                UrlGeneratorInterface::ABSOLUTE_PATH
            )
        );
    }

    public function testGetFilteredImageUrlKeepsAbsoluteUrlWithoutCheckingRequest(): void
    {
        $this->embeddedRequestChecker
            ->expects(self::never())
            ->method('isEmbeddedRequest');

        $this->innerImagineUrlProvider
            ->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with('bundles/oroproduct/images/no_image.png', 'product_large', '', UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://oro.loc/media/cache/resolve/product_large/bundles/oroproduct/images/no_image.png');

        self::assertSame(
            'http://oro.loc/media/cache/resolve/product_large/bundles/oroproduct/images/no_image.png',
            $this->provider->getFilteredImageUrl('bundles/oroproduct/images/no_image.png', 'product_large')
        );
    }
}

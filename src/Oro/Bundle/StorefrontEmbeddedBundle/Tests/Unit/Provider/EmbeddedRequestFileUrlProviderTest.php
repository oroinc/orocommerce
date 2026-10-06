<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\StorefrontEmbeddedBundle\Provider\EmbeddedRequestFileUrlProvider;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class EmbeddedRequestFileUrlProviderTest extends TestCase
{
    private FileUrlProviderInterface&MockObject $innerFileUrlProvider;
    private EmbeddedRequestChecker&MockObject $embeddedRequestChecker;
    private EmbeddedRequestFileUrlProvider $provider;
    private File $file;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerFileUrlProvider = $this->createMock(FileUrlProviderInterface::class);
        $this->embeddedRequestChecker = $this->createMock(EmbeddedRequestChecker::class);
        $this->file = new File();

        $this->provider = new EmbeddedRequestFileUrlProvider(
            $this->innerFileUrlProvider,
            $this->embeddedRequestChecker
        );
    }

    public function testGetFilteredImageUrlReturnsAbsoluteUrlForEmbeddedFragmentRequest(): void
    {
        $this->embeddedRequestChecker
            ->expects(self::once())
            ->method('isEmbeddedRequest')
            ->willReturn(true);

        $this->innerFileUrlProvider
            ->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($this->file, 'product_large', 'webp', UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://oro.loc/media/cache/attachment/filter/product_large/1/cup.jpg.webp');

        self::assertSame(
            'http://oro.loc/media/cache/attachment/filter/product_large/1/cup.jpg.webp',
            $this->provider->getFilteredImageUrl($this->file, 'product_large', 'webp')
        );
    }

    public function testGetResizedImageUrlReturnsAbsoluteUrlForEmbeddedFragmentRequest(): void
    {
        $this->embeddedRequestChecker
            ->expects(self::once())
            ->method('isEmbeddedRequest')
            ->willReturn(true);

        $this->innerFileUrlProvider
            ->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($this->file, 100, 80, '', UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://oro.loc/media/cache/attachment/resize/1/100/80/cup.jpg');

        self::assertSame(
            'http://oro.loc/media/cache/attachment/resize/1/100/80/cup.jpg',
            $this->provider->getResizedImageUrl($this->file, 100, 80)
        );
    }

    public function testGetFilteredImageUrlReturnsPathOutsideEmbeddedFragmentRequest(): void
    {
        $this->embeddedRequestChecker
            ->expects(self::once())
            ->method('isEmbeddedRequest')
            ->willReturn(false);

        $this->innerFileUrlProvider
            ->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($this->file, 'product_large', '', UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/media/cache/attachment/filter/product_large/1/cup.jpg');

        self::assertSame(
            '/media/cache/attachment/filter/product_large/1/cup.jpg',
            $this->provider->getFilteredImageUrl($this->file, 'product_large')
        );
    }

    public function testGetFilteredImageUrlKeepsNetworkPathWithoutCheckingRequest(): void
    {
        $this->embeddedRequestChecker
            ->expects(self::never())
            ->method('isEmbeddedRequest');

        $this->innerFileUrlProvider
            ->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($this->file, 'product_large', '', UrlGeneratorInterface::NETWORK_PATH)
            ->willReturn('//oro.loc/media/cache/attachment/filter/product_large/1/cup.jpg');

        self::assertSame(
            '//oro.loc/media/cache/attachment/filter/product_large/1/cup.jpg',
            $this->provider->getFilteredImageUrl($this->file, 'product_large', '', UrlGeneratorInterface::NETWORK_PATH)
        );
    }

    public function testGetFileUrlDelegatesToInnerProvider(): void
    {
        $this->embeddedRequestChecker
            ->expects(self::never())
            ->method('isEmbeddedRequest');

        $this->innerFileUrlProvider
            ->expects(self::once())
            ->method('getFileUrl')
            ->with($this->file, FileUrlProviderInterface::FILE_ACTION_DOWNLOAD, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/attachment/download/1/invoice.pdf');

        self::assertSame(
            '/attachment/download/1/invoice.pdf',
            $this->provider->getFileUrl($this->file, FileUrlProviderInterface::FILE_ACTION_DOWNLOAD)
        );
    }
}

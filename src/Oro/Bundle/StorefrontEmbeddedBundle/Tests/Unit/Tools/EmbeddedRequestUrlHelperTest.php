<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Tools;

use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Oro\Bundle\StorefrontEmbeddedBundle\Tools\EmbeddedRequestUrlHelper;
use Oro\Bundle\UIBundle\Tools\UrlHelperInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EmbeddedRequestUrlHelperTest extends TestCase
{
    private UrlHelperInterface&MockObject $innerUrlHelper;
    private EmbeddedRequestChecker&MockObject $embeddedRequestChecker;
    private EmbeddedRequestUrlHelper $urlHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerUrlHelper = $this->createMock(UrlHelperInterface::class);
        $this->embeddedRequestChecker = $this->createMock(EmbeddedRequestChecker::class);

        $this->urlHelper = new EmbeddedRequestUrlHelper($this->innerUrlHelper, $this->embeddedRequestChecker);
    }

    public function testGetAbsolutePathKeepsDeploymentSubFolderForEmbeddedFragmentRequest(): void
    {
        $this->embeddedRequestChecker
            ->expects(self::once())
            ->method('isEmbeddedRequest')
            ->willReturn(true);

        $this->innerUrlHelper
            ->expects(self::once())
            ->method('getAbsolutePath')
            ->with('/media/cache/attachment/filter/product_large/1/cup.jpg')
            ->willReturn('/shop/media/cache/attachment/filter/product_large/1/cup.jpg');
        $this->innerUrlHelper
            ->expects(self::once())
            ->method('getAbsoluteUrl')
            ->with('/shop/media/cache/attachment/filter/product_large/1/cup.jpg')
            ->willReturn('http://oro.loc/shop/media/cache/attachment/filter/product_large/1/cup.jpg');

        self::assertSame(
            'http://oro.loc/shop/media/cache/attachment/filter/product_large/1/cup.jpg',
            $this->urlHelper->getAbsolutePath('/media/cache/attachment/filter/product_large/1/cup.jpg')
        );
    }

    public function testGetAbsolutePathKeepsPathOutsideEmbeddedFragmentRequest(): void
    {
        $this->embeddedRequestChecker
            ->expects(self::once())
            ->method('isEmbeddedRequest')
            ->willReturn(false);

        $this->innerUrlHelper
            ->expects(self::once())
            ->method('getAbsolutePath')
            ->with('/media/cache/attachment/filter/product_large/1/cup.jpg')
            ->willReturn('/media/cache/attachment/filter/product_large/1/cup.jpg');
        $this->innerUrlHelper
            ->expects(self::never())
            ->method('getAbsoluteUrl');

        self::assertSame(
            '/media/cache/attachment/filter/product_large/1/cup.jpg',
            $this->urlHelper->getAbsolutePath('/media/cache/attachment/filter/product_large/1/cup.jpg')
        );
    }

    public function testGetAbsoluteUrlDelegatesToInnerHelper(): void
    {
        $this->embeddedRequestChecker
            ->expects(self::never())
            ->method('isEmbeddedRequest');

        $this->innerUrlHelper
            ->expects(self::once())
            ->method('getAbsoluteUrl')
            ->with('/media/cache/attachment/filter/product_large/1/cup.jpg')
            ->willReturn('http://oro.loc/media/cache/attachment/filter/product_large/1/cup.jpg');

        self::assertSame(
            'http://oro.loc/media/cache/attachment/filter/product_large/1/cup.jpg',
            $this->urlHelper->getAbsoluteUrl('/media/cache/attachment/filter/product_large/1/cup.jpg')
        );
    }

    public function testGetRelativePathDelegatesToInnerHelper(): void
    {
        $this->embeddedRequestChecker
            ->expects(self::never())
            ->method('isEmbeddedRequest');

        $this->innerUrlHelper
            ->expects(self::once())
            ->method('getRelativePath')
            ->with('/media/cache/attachment/filter/product_large/1/cup.jpg')
            ->willReturn('cup.jpg');

        self::assertSame(
            'cup.jpg',
            $this->urlHelper->getRelativePath('/media/cache/attachment/filter/product_large/1/cup.jpg')
        );
    }

    public function testIsLocalUrlDelegatesToInnerHelper(): void
    {
        $this->embeddedRequestChecker
            ->expects(self::never())
            ->method('isEmbeddedRequest');

        $this->innerUrlHelper
            ->expects(self::once())
            ->method('isLocalUrl')
            ->with('http://oro.loc/')
            ->willReturn(false);

        self::assertFalse($this->urlHelper->isLocalUrl('http://oro.loc/'));
    }
}

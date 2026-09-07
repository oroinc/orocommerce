<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Tools;

use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Oro\Bundle\StorefrontEmbeddedBundle\Tools\EmbeddedRequestUrlHelper;
use Oro\Bundle\UIBundle\Tools\UrlHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmbeddedRequestUrlHelperTest extends TestCase
{
    private const string IMAGE_PATH = '/media/cache/attachment/filter/product_large/1/cup.jpg';

    private UrlHelper&MockObject $innerUrlHelper;
    private EmbeddedRequestChecker&MockObject $embeddedRequestChecker;
    private EmbeddedRequestUrlHelper $urlHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerUrlHelper = $this->createMock(UrlHelper::class);
        $this->embeddedRequestChecker = $this->createMock(EmbeddedRequestChecker::class);

        $this->urlHelper = new EmbeddedRequestUrlHelper($this->innerUrlHelper, $this->embeddedRequestChecker);
    }

    public function testTurnsAnIndexedImagePathIntoAnOroUrlOnAnEmbeddedRequest(): void
    {
        $this->embedded(true);

        $this->innerUrlHelper->expects(self::once())
            ->method('getAbsolutePath')
            ->with(self::IMAGE_PATH)
            ->willReturn(self::IMAGE_PATH);
        $this->innerUrlHelper->expects(self::once())
            ->method('getAbsoluteUrl')
            ->with(self::IMAGE_PATH)
            ->willReturn('http://oro.loc' . self::IMAGE_PATH);

        self::assertSame('http://oro.loc' . self::IMAGE_PATH, $this->urlHelper->getAbsolutePath(self::IMAGE_PATH));
    }

    public function testKeepsTheSubFolderOfTheDeploymentOnAnEmbeddedRequest(): void
    {
        $this->embedded(true);

        $this->innerUrlHelper->expects(self::once())
            ->method('getAbsolutePath')
            ->with(self::IMAGE_PATH)
            ->willReturn('/shop' . self::IMAGE_PATH);
        $this->innerUrlHelper->expects(self::once())
            ->method('getAbsoluteUrl')
            ->with('/shop' . self::IMAGE_PATH)
            ->willReturn('http://oro.loc/shop' . self::IMAGE_PATH);

        self::assertSame(
            'http://oro.loc/shop' . self::IMAGE_PATH,
            $this->urlHelper->getAbsolutePath(self::IMAGE_PATH)
        );
    }

    public function testKeepsThePathOnOroOwnPages(): void
    {
        $this->embedded(false);

        $this->innerUrlHelper->expects(self::once())
            ->method('getAbsolutePath')
            ->with(self::IMAGE_PATH)
            ->willReturn(self::IMAGE_PATH);
        $this->innerUrlHelper->expects(self::never())
            ->method('getAbsoluteUrl');

        self::assertSame(self::IMAGE_PATH, $this->urlHelper->getAbsolutePath(self::IMAGE_PATH));
    }

    public function testDelegatesEverythingElse(): void
    {
        $this->embeddedRequestChecker->expects(self::never())
            ->method('isEmbeddedRequest');

        $this->innerUrlHelper->expects(self::once())
            ->method('getAbsoluteUrl')
            ->with(self::IMAGE_PATH)
            ->willReturn('http://oro.loc' . self::IMAGE_PATH);
        $this->innerUrlHelper->expects(self::once())
            ->method('getRelativePath')
            ->with(self::IMAGE_PATH)
            ->willReturn('cup.jpg');
        $this->innerUrlHelper->expects(self::once())
            ->method('isLocalUrl')
            ->with('http://oro.loc/')
            ->willReturn(false);

        self::assertSame('http://oro.loc' . self::IMAGE_PATH, $this->urlHelper->getAbsoluteUrl(self::IMAGE_PATH));
        self::assertSame('cup.jpg', $this->urlHelper->getRelativePath(self::IMAGE_PATH));
        self::assertFalse($this->urlHelper->isLocalUrl('http://oro.loc/'));
    }

    private function embedded(bool $isEmbeddedRequest): void
    {
        $this->embeddedRequestChecker->expects(self::once())
            ->method('isEmbeddedRequest')
            ->willReturn($isEmbeddedRequest);
    }
}

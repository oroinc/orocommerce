<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentVariantRepository;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentVariantProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestWebContentVariantProviderTest extends \PHPUnit\Framework\TestCase
{
    private RequestStack|\PHPUnit\Framework\MockObject\MockObject $requestStack;

    private ContentVariantRepository|\PHPUnit\Framework\MockObject\MockObject $contentVariantRepository;

    private RequestWebContentVariantProvider $provider;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->contentVariantRepository = $this->createMock(ContentVariantRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getRepository')
            ->with(ContentVariant::class)
            ->willReturn($this->contentVariantRepository);

        $this->provider = new RequestWebContentVariantProvider(
            $this->requestStack,
            $doctrine
        );
    }

    public function testGetContentVariantWhenNoRequest(): void
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);
        $this->requestStack->expects($this->never())
            ->method('getMainRequest');
        $this->contentVariantRepository->expects($this->never())
            ->method('findVariantBySlug');

        self::assertNull($this->provider->getContentVariant());
    }

    public function testGetContentVariantForSubRequest(): void
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->createMock(Request::class));
        $this->requestStack->expects($this->once())
            ->method('getMainRequest')
            ->willReturn($this->createMock(Request::class));
        $this->contentVariantRepository->expects($this->never())
            ->method('findVariantBySlug');

        self::assertNull($this->provider->getContentVariant());
    }

    public function testGetContentVariantWhenNotSlug(): void
    {
        $request = Request::create('/');

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->requestStack->expects($this->once())
            ->method('getMainRequest')
            ->willReturn($request);
        $this->contentVariantRepository->expects($this->never())
            ->method('findVariantBySlug');

        self::assertNull($this->provider->getContentVariant());
        self::assertTrue($request->attributes->has('_content_variant'));
        self::assertNull($request->attributes->get('_content_variant'));
    }

    public function testGetContentVariant(): void
    {
        $request = Request::create('/');

        $slug = $this->createMock(Slug::class);
        $request->attributes->set('_used_slug', $slug);

        $variant = $this->createMock(ContentVariant::class);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->requestStack->expects($this->once())
            ->method('getMainRequest')
            ->willReturn($request);
        $this->contentVariantRepository->expects($this->once())
            ->method('findVariantBySlug')
            ->with($slug)
            ->willReturn($variant);

        self::assertSame($variant, $this->provider->getContentVariant());
        self::assertTrue($request->attributes->has('_content_variant'));
        self::assertSame($variant, $request->attributes->get('_content_variant'));
    }

    public function testGetContentVariantWhenVariantNotAttachedToSlug(): void
    {
        $request = Request::create('/');

        $slug = $this->createMock(Slug::class);
        $request->attributes->set('_used_slug', $slug);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->requestStack->expects($this->once())
            ->method('getMainRequest')
            ->willReturn($request);
        $this->contentVariantRepository->expects($this->once())
            ->method('findVariantBySlug')
            ->with($slug)
            ->willReturn(null);

        self::assertNull($this->provider->getContentVariant());
        self::assertTrue($request->attributes->has('_content_variant'));
        self::assertNull($request->attributes->get('_content_variant'));
    }
}

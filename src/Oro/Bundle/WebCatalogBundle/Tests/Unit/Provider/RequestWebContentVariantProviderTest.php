<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentVariantRepository;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentVariantProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestWebContentVariantProviderTest extends TestCase
{
    private RequestStack|MockObject $requestStack;

    private ContentVariantRepository|MockObject $contentVariantRepository;

    private RequestWebContentVariantProvider $provider;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->contentVariantRepository = $this->createMock(ContentVariantRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
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
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn(null);
        $this->contentVariantRepository->expects(self::never())
            ->method('findVariantBySlug');

        self::assertNull($this->provider->getContentVariant());
    }

    public function testGetContentVariantWhenNotSlug(): void
    {
        $request = Request::create('/');
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->contentVariantRepository->expects(self::never())
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

        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->contentVariantRepository->expects(self::once())
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

        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->contentVariantRepository->expects(self::once())
            ->method('findVariantBySlug')
            ->with($slug)
            ->willReturn(null);

        self::assertNull($this->provider->getContentVariant());
        self::assertTrue($request->attributes->has('_content_variant'));
        self::assertNull($request->attributes->get('_content_variant'));
    }
}

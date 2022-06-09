<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\EventListener\WebpAwareWebsiteSearchProductIndexerListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class WebpAwareWebsiteSearchProductIndexerListenerTest extends \PHPUnit\Framework\TestCase
{
    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $managerRegistry;

    private AttachmentManager|\PHPUnit\Framework\MockObject\MockObject $attachmentManager;

    private WebsiteContextManager|\PHPUnit\Framework\MockObject\MockObject $websiteContextManager;

    private WebpAwareWebsiteSearchProductIndexerListener $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->websiteContextManager = $this->createMock(WebsiteContextManager::class);

        $this->listener = new WebpAwareWebsiteSearchProductIndexerListener(
            $this->managerRegistry,
            $this->attachmentManager,
            $this->websiteContextManager
        );
    }

    public function testOnWebsiteSearchIndexWebpStrategyNotSupportedFieldsGroup(): void
    {
        $this->attachmentManager
            ->expects(self::never())
            ->method('isWebpEnabledIfSupported');

        $event = new IndexEntityEvent(Product::class, [], [AbstractIndexer::CONTEXT_FIELD_GROUPS => ['main']]);

        $this->listener->onWebsiteSearchIndex($event);

        self::assertFalse($event->isPropagationStopped());
        self::assertEquals([], $event->getEntitiesData());
    }

    /**
     * @dataProvider validContextDataProvider
     */
    public function testOnWebsiteSearchIndexWebpStrategyNotEnabledIfSupported(array $context): void
    {
        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(false);

        $event = new IndexEntityEvent(Product::class, [], $context);

        $this->listener->onWebsiteSearchIndex($event);

        self::assertFalse($event->isPropagationStopped());
        self::assertEquals([], $event->getEntitiesData());
    }

    /**
     * @dataProvider validContextDataProvider
     */
    public function testOnWebsiteSearchIndexNoWebsite(array $context): void
    {
        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(true);

        $event = new IndexEntityEvent(Product::class, [], $context);

        $this->listener->onWebsiteSearchIndex($event);

        self::assertTrue($event->isPropagationStopped());
        self::assertEquals([], $event->getEntitiesData());
    }

    /**
     * @dataProvider validContextDataProvider
     */
    public function testOnWebsiteSearchIndexNoProducts(array $context): void
    {
        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(true);

        $organization = new Organization();
        $website = new Website();
        $website->setOrganization($organization);

        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects(self::once())
            ->method('find')
            ->with(Website::class, 1)
            ->willReturn($website);

        $productRepository = $this->createMock(ProductRepository::class);

        $this->managerRegistry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($em);
        $this->managerRegistry
            ->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([
                [Product::class, null, $productRepository],
            ]);

        $this->websiteContextManager
            ->expects(self::once())
            ->method('getWebsiteId')
            ->with($context)
            ->willReturn(1);

        $event = new IndexEntityEvent(Product::class, [], $context);

        $this->listener->onWebsiteSearchIndex($event);

        self::assertFalse($event->isPropagationStopped());
        self::assertEquals([], $event->getEntitiesData());
    }

    /**
     * @dataProvider validContextDataProvider
     */
    public function testOnWebsiteSearch(array $context): void
    {
        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(true);

        $organization = new Organization();
        $website = new Website();
        $website->setOrganization($organization);

        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects(self::once())
            ->method('find')
            ->with(Website::class, 1)
            ->willReturn($website);

        $productFooId = 1;
        $productFoo = (new ProductStub())
            ->setId($productFooId)
            ->setSku('FOO');

        $productBarId = 2;
        $productBar = (new ProductStub())
            ->setId($productBarId)
            ->setSku('BAR');

        $image = (new File())
            ->setFilename('image1.jpg');

        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository
            ->expects(self::once())
            ->method('getListingImagesFilesByProductIds')
            ->with([$productFooId, $productBarId])
            ->willReturn([$productFooId => $image]);

        $this->managerRegistry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($em);
        $this->managerRegistry
            ->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([
                [Product::class, null, $productRepository],
            ]);

        $this->websiteContextManager
            ->expects(self::once())
            ->method('getWebsiteId')
            ->with($context)
            ->willReturn(1);

        $event = new IndexEntityEvent(Product::class, [$productFoo, $productBar], $context);

        $this->attachmentManager
            ->expects(self::exactly(3))
            ->method('getFilteredImageUrl')
            ->willReturnMap([
                [$image, 'product_large', 'webp', UrlGeneratorInterface::ABSOLUTE_PATH, '/large/image/webp'],
                [$image, 'product_medium', 'webp', UrlGeneratorInterface::ABSOLUTE_PATH, '/medium/image/webp'],
                [$image, 'product_small', 'webp', UrlGeneratorInterface::ABSOLUTE_PATH, '/small/image/webp']
            ]);

        $this->listener->onWebsiteSearchIndex($event);

        self::assertFalse($event->isPropagationStopped());
        self::assertEquals(
            [
                $productFooId => [
                    'image_product_large_webp' => [
                        [
                            'value' => '/large/image/webp',
                            'all_text' => false,
                        ]
                    ],
                    'image_product_medium_webp' => [
                        [
                            'value' => '/medium/image/webp',
                            'all_text' => false,
                        ]
                    ],
                    'image_product_small_webp' => [
                        [
                            'value' => '/small/image/webp',
                            'all_text' => false,
                        ]
                    ],
                ],
            ],
            $event->getEntitiesData()
        );
    }

    public function validContextDataProvider(): \Generator
    {
        yield [[]];
        yield [[AbstractIndexer::CONTEXT_FIELD_GROUPS => ['image']]];
    }
}

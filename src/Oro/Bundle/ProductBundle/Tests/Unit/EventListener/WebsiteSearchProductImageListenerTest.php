<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\EventListener\WebsiteSearchProductImageListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class WebsiteSearchProductImageListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $managerRegistry;

    /**
     * @var AttachmentManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $attachmentManager;

    /**
     * @var WebsiteContextManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteContextManager;

    private WebsiteSearchProductImageListener $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->websiteContextManager = $this->createMock(WebsiteContextManager::class);

        $this->listener = new WebsiteSearchProductImageListener(
            $this->websiteContextManager,
            $this->managerRegistry,
            $this->attachmentManager
        );
    }

    public function testOnWebsiteSearchIndexWebpStrategyNotSupportedFieldsGroup(): void
    {
        $this->managerRegistry
            ->expects(self::never())
            ->method($this->anything());

        $event = new IndexEntityEvent(Product::class, [], [AbstractIndexer::CONTEXT_FIELD_GROUPS => ['main']]);

        $this->listener->onWebsiteSearchIndex($event);

        self::assertFalse($event->isPropagationStopped());
        self::assertEquals([], $event->getEntitiesData());
    }

    /**
     * @dataProvider validContextDataProvider
     */
    public function testOnWebsiteSearchIndexNoWebsite(array $context): void
    {
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
        $productRepository = $this->createMock(ProductRepository::class);
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
                [$image, 'product_large', '', UrlGeneratorInterface::ABSOLUTE_PATH, '/large/image/img'],
                [$image, 'product_medium', '', UrlGeneratorInterface::ABSOLUTE_PATH, '/medium/image/img'],
                [$image, 'product_small', '', UrlGeneratorInterface::ABSOLUTE_PATH, '/small/image/img']
            ]);

        $this->listener->onWebsiteSearchIndex($event);

        self::assertFalse($event->isPropagationStopped());
        self::assertEquals(
            [
                $productFooId => [
                    'image_product_large' => [
                        [
                            'value' => '/large/image/img',
                            'all_text' => false,
                        ]
                    ],
                    'image_product_medium' => [
                        [
                            'value' => '/medium/image/img',
                            'all_text' => false,
                        ]
                    ],
                    'image_product_small' => [
                        [
                            'value' => '/small/image/img',
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

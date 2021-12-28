<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeLoadedData\CustomizeLoadedDataProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\ProductBundle\Api\Processor\ComputeWebpAwareImageFilePath;
use Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor\Stub\ProductImageStub;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ComputeWebpAwareImageFilePathTest extends CustomizeLoadedDataProcessorTestCase
{
    private AttachmentManager|\PHPUnit\Framework\MockObject\MockObject $attachmentManager;

    private EntityRepository|\PHPUnit\Framework\MockObject\MockObject $repository;

    private ComputeWebpAwareImageFilePath $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->repository = $this->createMock(EntityRepository::class);

        $doctrineHelper->expects(self::any())
            ->method('getEntityRepository')
            ->willReturn($this->repository);

        $this->processor = new ComputeWebpAwareImageFilePath(
            $this->attachmentManager,
            $doctrineHelper
        );
    }

    public function testProcessDoNothingIfNotIsWebpEnabledIfSupported(): void
    {
        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(false);

        $initialResults = [
            'id' => 1,
            'type' => 'files',
            'filePath' => [
                [
                    'url' => '/test/url.jpg',
                    'dimension' => 'test_dimension',
                ]
            ]
        ];
        $this->context->setResult($initialResults);
        $this->processor->process($this->context);

        self::assertEquals($initialResults, $this->context->getResult());
    }

    /**
     * @dataProvider getProcessDoNothingIfNoFilePathDataProvider
     */
    public function testProcessDoNothingIfNoFilePath(?array $initialFilePath): void
    {
        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(true);

        $initialResults = [
            'id' => 1,
            'type' => 'files',
            'filePath' => $initialFilePath
        ];
        $this->context->setResult($initialResults);
        $this->processor->process($this->context);

        self::assertEquals($initialResults, $this->context->getResult());
    }

    public function getProcessDoNothingIfNoFilePathDataProvider(): array
    {
        return [
            [
                'initialFilePath' => null,
            ],
            [
                'initialFilePath' => [],
            ],
        ];
    }

    public function testProcessDoNothingIfNoFileIdFieldName(): void
    {
        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(true);

        $initialResults = [
            'type' => 'files',
            'filePath' => [
                [
                    'url' => '/test/url.jpg',
                    'dimension' => 'test_dimension',
                ]
            ]
        ];
        $this->context->setResult($initialResults);
        $this->processor->process($this->context);

        self::assertEquals($initialResults, $this->context->getResult());
    }

    public function testProcessDoNothingIfNoProductImage(): void
    {
        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(true);

        $initialResults = [
            'id' => 1,
            'type' => 'files',
            'filePath' => [
                [
                    'url' => '/test/url.jpg',
                    'dimension' => 'test_dimension',
                ]
            ]
        ];

        $this->repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['image' => $initialResults['id']])
            ->willReturn(null);

        $this->context->setResult($initialResults);
        $this->processor->process($this->context);

        self::assertEquals($initialResults, $this->context->getResult());
    }

    public function testProcess(): void
    {
        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(true);

        $initialResults = [
            'id' => 1,
            'type' => 'files',
            'filePath' => [
                [
                    'url' => '/test/url1.jpg',
                    'dimension' => 'test_dimension',
                ],
                [
                    'url' => '/test/url2.jpg',
                ],
            ]
        ];

        $imageFile = $this->createMock(File::class);
        $productImage = new ProductImageStub();
        $productImage->setImage($imageFile);

        $this->repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['image' => $initialResults['id']])
            ->willReturn($productImage);

        $webpUrl = '/test/url1.jpg.webp';
        $this->attachmentManager
            ->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with(
                $imageFile,
                'test_dimension',
                'webp',
                UrlGeneratorInterface::ABSOLUTE_PATH
            )
            ->willReturn($webpUrl);

        $this->context->setResult($initialResults);
        $this->processor->process($this->context);

        $expectedResults = $initialResults;
        $expectedResults['filePath'][0]['url_webp'] = $webpUrl;

        self::assertEquals($expectedResults, $this->context->getResult());
    }
}

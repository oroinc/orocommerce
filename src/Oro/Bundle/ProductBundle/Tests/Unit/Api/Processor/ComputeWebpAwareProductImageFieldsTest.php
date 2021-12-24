<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeLoadedData\CustomizeLoadedDataProcessorTestCase;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\ProductBundle\Api\Processor\ComputeWebpAwareProductImageFields;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ComputeWebpAwareProductImageFieldsTest extends CustomizeLoadedDataProcessorTestCase
{
    private AttachmentManager|\PHPUnit\Framework\MockObject\MockObject $attachmentManager;

    private ComputeWebpAwareProductImageFields $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attachmentManager = $this->createMock(AttachmentManager::class);

        $this->processor = new ComputeWebpAwareProductImageFields($this->attachmentManager);
    }

    public function testProcessDoNothingIfNotIsWebpEnabledIfSupported(): void
    {
        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(false);

        $initialResults = [
            [
                'id' => 1,
                'type' => 'productimages',
                'files' => [
                    [
                        'url' => '/test/url.jpg',
                        'dimension' => 'test_dimension',
                    ],
                ],
            ]
        ];
        $this->context->setResult($initialResults);
        $this->processor->process($this->context);

        self::assertEquals($initialResults, $this->context->getResult());
    }

    public function testProcessDoNothingIfEmptyFiles(): void
    {
        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(true);

        $initialResults = [
            [
                'id' => 1,
                'type' => 'productimages',
                'image' => [
                    'id' => 1,
                ],
            ],
            [
                'id' => 2,
                'type' => 'productimages',
                'image' => [
                    'id' => 2,
                ],
                'files' => [],
            ],
        ];
        $this->context->setResult($initialResults);
        $this->processor->process($this->context);

        self::assertEquals($initialResults, $this->context->getResult());
    }

    public function testProcessDoNothingIfNoImage(): void
    {
        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(true);

        $initialResults = [
            [
                'id' => 1,
                'type' => 'productimages',
                'files' => [
                    [
                        'url' => '/test/url.jpg',
                        'dimension' => 'test_dimension',
                    ],
                ],
            ],
        ];
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

        $imageFileId = 1;
        $imageFileName = 'imageFileName.jpg';
        $initialResults = [
            [
                'id' => 1,
                'type' => 'productimages',
                'image' => [
                    'id' => $imageFileId,
                    'filename' => $imageFileName,
                ],
                'files' => [
                    [
                        'url' => '/test/url.jpg',
                        'dimension' => 'test_dimension',
                    ],
                ],
            ],
        ];

        $webpUrl = '/test/url1.jpg.webp';
        $this->attachmentManager
            ->expects(self::once())
            ->method('getFilteredImageUrlByIdAndFilename')
            ->with(
                $imageFileId,
                $imageFileName,
                'test_dimension',
                'webp',
                UrlGeneratorInterface::ABSOLUTE_PATH
            )
            ->willReturn($webpUrl);

        $this->context->setResult($initialResults);
        $this->processor->process($this->context);

        $expectedResults = $initialResults;
        $expectedResults[0]['files'][0]['url_webp'] = $webpUrl;

        self::assertEquals($expectedResults, $this->context->getResult());
    }
}

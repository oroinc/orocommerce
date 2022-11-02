<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeLoadedData\CustomizeLoadedDataProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Api\Processor\ComputeImageFilePath;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;

class ComputeImageFilePathTest extends CustomizeLoadedDataProcessorTestCase
{
    /** @var AttachmentManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentManager;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ImageTypeProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $typeProvider;

    /** @var ComputeImageFilePath */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->typeProvider = $this->createMock(ImageTypeProvider::class);

        $this->processor = new ComputeImageFilePath(
            $this->attachmentManager,
            $this->doctrineHelper,
            $this->typeProvider
        );
    }

    private function expectsGetTypesQuery(int $fileId, array $typesData): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $qb->expects(self::once())
            ->method('select')
            ->with('imageType.type')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('innerJoin')
            ->with('imageType.productImage', 'image')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('where')
            ->with('image.image = :fileId')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('setParameter')
            ->with('fileId', $fileId)
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn($typesData);

        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with(ProductImageType::class, 'imageType')
            ->willReturn($qb);
    }

    private function getImageType(array $dimensions): ThemeImageType
    {
        $type = $this->createMock(ThemeImageType::class);
        $type->expects(self::any())
            ->method('getDimensions')
            ->willReturn($dimensions);

        return $type;
    }

    private function getImageTypeDimension(): ThemeImageTypeDimension
    {
        return $this->createMock(ThemeImageTypeDimension::class);
    }

    public function testProcess(): void
    {
        $config = new EntityDefinitionConfig();
        $config->addField('id');
        $config->addField('filePath');
        $config->addField('mimeType');
        $config->addField('filename');

        $data = [
            'id'       => 1,
            'content'  => 'testContent',
            'mimeType' => 'testMime',
            'filename' => 'test1.jpg'
        ];

        $this->attachmentManager->expects(self::once())
            ->method('isImageType')
            ->with($data['mimeType'])
            ->willReturn(true);

        $this->expectsGetTypesQuery($data['id'], [['type' => 'type1']]);
        $this->typeProvider->expects(self::once())
            ->method('getImageTypes')
            ->willReturn(
                [
                    'type1' => $this->getImageType(['small' => $this->getImageTypeDimension()])
                ]
            );

        $this->attachmentManager->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(false);
        $this->attachmentManager->expects(self::once())
            ->method('getFilteredImageUrlByIdAndFilename')
            ->with($data['id'], $data['filename'], 'small')
            ->willReturn('testUrl');

        $this->context->setConfig($config);
        $this->context->setData($data);
        $this->processor->process($this->context);

        self::assertEquals(
            array_merge($data, [
                'filePath' => [
                    [
                        'url'       => 'testUrl',
                        'dimension' => 'small'
                    ]
                ]
            ]),
            $this->context->getData()
        );
    }

    public function testProcessForWebpEnabled(): void
    {
        $config = new EntityDefinitionConfig();
        $config->addField('id');
        $config->addField('filePath');
        $config->addField('mimeType');
        $config->addField('filename');

        $data = [
            'id'       => 1,
            'content'  => 'testContent',
            'mimeType' => 'testMime',
            'filename' => 'test1.jpg'
        ];

        $this->attachmentManager->expects(self::once())
            ->method('isImageType')
            ->with($data['mimeType'])
            ->willReturn(true);

        $this->expectsGetTypesQuery($data['id'], [['type' => 'type1']]);
        $this->typeProvider->expects(self::once())
            ->method('getImageTypes')
            ->willReturn(
                [
                    'type1' => $this->getImageType(['small' => $this->getImageTypeDimension()])
                ]
            );

        $this->attachmentManager->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(true);
        $this->attachmentManager->expects(self::exactly(2))
            ->method('getFilteredImageUrlByIdAndFilename')
            ->withConsecutive(
                [$data['id'], $data['filename'], 'small'],
                [$data['id'], $data['filename'], 'small', 'webp']
            )
            ->willReturnOnConsecutiveCalls(
                'testUrl',
                'testWebpUrl'
            );

        $this->context->setConfig($config);
        $this->context->setData($data);
        $this->processor->process($this->context);

        self::assertEquals(
            array_merge($data, [
                'filePath' => [
                    [
                        'url'       => 'testUrl',
                        'dimension' => 'small',
                        'url_webp'  => 'testWebpUrl'
                    ]
                ]
            ]),
            $this->context->getData()
        );
    }

    public function testProcessWhenProductImageTypesAreEmpty(): void
    {
        $config = new EntityDefinitionConfig();
        $config->addField('id');
        $config->addField('filePath');
        $config->addField('mimeType');
        $config->addField('filename');

        $data = [
            'id'       => 1,
            'content'  => 'testContent',
            'mimeType' => 'testMime',
            'filename' => 'test1.jpg'
        ];

        $this->attachmentManager->expects(self::once())
            ->method('isImageType')
            ->with($data['mimeType'])
            ->willReturn(true);

        $this->expectsGetTypesQuery($data['id'], []);
        $this->typeProvider->expects(self::never())
            ->method('getImageTypes');

        $this->attachmentManager->expects(self::never())
            ->method('isWebpEnabledIfSupported');
        $this->attachmentManager->expects(self::never())
            ->method('getFilteredImageUrlByIdAndFilename');

        $this->context->setConfig($config);
        $this->context->setData($data);
        $this->processor->process($this->context);

        self::assertEquals($data, $this->context->getData());
    }

    public function testProcessWhenMimeTypeIsNotImage(): void
    {
        $config = new EntityDefinitionConfig();
        $config->addField('id');
        $config->addField('filePath');
        $config->addField('mimeType');
        $config->addField('filename');

        $data = [
            'id'       => 1,
            'content'  => 'testContent',
            'mimeType' => 'testMime',
            'filename' => 'test1.jpg'
        ];

        $this->attachmentManager->expects(self::once())
            ->method('isImageType')
            ->with($data['mimeType'])
            ->willReturn(false);

        $this->doctrineHelper->expects(self::never())
            ->method('createQueryBuilder');
        $this->typeProvider->expects(self::never())
            ->method('getImageTypes');

        $this->attachmentManager->expects(self::never())
            ->method('isWebpEnabledIfSupported');
        $this->attachmentManager->expects(self::never())
            ->method('getFilteredImageUrlByIdAndFilename');

        $this->context->setConfig($config);
        $this->context->setData($data);
        $this->processor->process($this->context);

        self::assertEquals($data, $this->context->getData());
    }

    public function testProcessWhenFilePathFieldNotRequested(): void
    {
        $config = new EntityDefinitionConfig();
        $config->addField('id');
        $config->addField('filePath')->setExcluded();
        $config->addField('mimeType');
        $config->addField('filename');

        $data = [
            'id'       => 1,
            'content'  => 'testContent',
            'mimeType' => 'testMime',
            'filename' => 'test1.jpg'
        ];

        $this->attachmentManager->expects(self::never())
            ->method('isImageType');

        $this->context->setConfig($config);
        $this->context->setData($data);
        $this->processor->process($this->context);

        self::assertEquals($data, $this->context->getData());
    }

    /**
     * @dataProvider notFullDataProvider
     */
    public function testProcessWhenDataAreNotFull(array $data): void
    {
        $config = new EntityDefinitionConfig();
        $config->addField('id');
        $config->addField('filePath');
        $config->addField('mimeType');
        $config->addField('filename');

        $this->attachmentManager->expects(self::never())
            ->method('isImageType');

        $this->context->setConfig($config);
        $this->context->setData($data);
        $this->processor->process($this->context);

        self::assertEquals($data, $this->context->getData());
    }

    public function notFullDataProvider(): array
    {
        return [
            [[]],
            [['id' => 1, 'mimeType' => '', 'filename' => 'test1.jpg']],
            [['id' => 1, 'mimeType' => 'testMime', 'filename' => '']],
            [['id' => 1, 'mimeType' => 'testMime']],
        ];
    }
}

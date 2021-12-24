<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeLoadedData\CustomizeLoadedDataProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Api\Processor\ComputeImageFilePath;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor\Stub\ProductImageStub;

class ComputeImageFilePathTest extends CustomizeLoadedDataProcessorTestCase
{
    /** @var AttachmentManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentManager;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ImageTypeProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $typeProvider;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repo;

    /** @var ComputeImageFilePath */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->typeProvider = $this->createMock(ImageTypeProvider::class);
        $this->repo = $this->createMock(EntityRepository::class);

        $this->doctrineHelper->expects(self::any())
            ->method('getEntityRepository')
            ->willReturn($this->repo);

        $this->processor = new ComputeImageFilePath(
            $this->attachmentManager,
            $this->doctrineHelper,
            $this->typeProvider
        );
    }

    /**
     * @dataProvider getTestProcessShouldHandlePathsCorrectlyProvider
     */
    public function testProcessShouldHandlePathsCorrectly(
        array $initialResults,
        bool $isImageType,
        ?ProductImage $productImage,
        array $expectedResults
    ): void {
        $type1 = $this->createMock(ThemeImageType::class);
        $type1->expects(self::any())
            ->method('getDimensions')
            ->willReturn(['testDimension' => [1, 2, 3]]);

        $allTypes = ['type1' => $type1];
        $this->typeProvider->expects(self::any())
            ->method('getImageTypes')
            ->willReturn($allTypes);

        $this->attachmentManager->expects(self::any())
            ->method('getFilteredImageUrl')
            ->willReturn('testUrl');
        $this->attachmentManager->expects(self::any())
            ->method('isImageType')
            ->willReturn($isImageType);

        $this->repo->expects(self::any())
            ->method('findOneBy')
            ->with(['image' => $initialResults['id']])
            ->willReturn($productImage);

        $entityConfig = new EntityDefinitionConfig();
        $entityConfig->addField('filePath');
        $entityConfig->addField('mimeType');
        $entityConfig->addField('id');

        $this->context->setConfig($entityConfig);
        $this->context->setResult($initialResults);
        $this->processor->process($this->context);

        self::assertEquals($expectedResults, $this->context->getResult());
    }

    public function getTestProcessShouldHandlePathsCorrectlyProvider(): array
    {
        $basicInitialResults = ['id' => 1, 'content' => 'testContent', 'mimeType' => 'testMime'];
        $productImageType = $this->createMock(ProductImageType::class);
        $productImageType->expects(self::any())
            ->method('getType')
            ->willReturn('type1');
        $productImage = $this->createMock(ProductImageStub::class);
        $productImage->expects(self::any())
            ->method('getTypes')
            ->willReturn([$productImageType]);
        $imageFile = $this->createMock(File::class);
        $productImage->expects(self::any())
            ->method('getImage')
            ->willReturn($imageFile);

        return [
            [
                'initialResults'  => $basicInitialResults,
                'isImageType'     => false,
                'productImage'    => null,
                'expectedResults' => $basicInitialResults,
            ],
            [
                'initialResults'  => $basicInitialResults,
                'isImageType'     => true,
                'productImage'    => $productImage,
                'expectedResults' => array_merge(
                    $basicInitialResults,
                    [
                        'filePath' => [
                            [
                                'url' => 'testUrl',
                                'dimension' => 'testDimension'
                            ],
                        ]
                    ]
                ),
            ],
        ];
    }
}

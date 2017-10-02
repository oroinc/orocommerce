<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor\Shared;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Api\Processor\Shared\ProcessImagePaths;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor\Stub\ProductImageStub;

class ProcessImagePathsTest extends GetProcessorTestCase
{
    /**
     * @var AttachmentManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attachmentManager;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var ImageTypeProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $typeProvider;

    /**
     * @var ProcessImagePaths
     */
    protected $processor;

    /**
     * @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repo;

    protected function setUp()
    {
        parent::setUp();

        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->typeProvider = $this->createMock(ImageTypeProvider::class);
        $this->repo = $this->createMock(EntityRepository::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($this->repo);

        $this->processor = new ProcessImagePaths(
            $this->attachmentManager,
            $this->doctrineHelper,
            $this->typeProvider
        );
    }

    /**
     * @dataProvider getTestProcessShouldHandlePathsCorrectlyProvider
     */
    public function testProcessShouldHandlePathsCorrectly(
        $initialResults,
        $isImageType,
        $productImage,
        $expectedResults
    ) {
        $type1 = $this->createMock(ThemeImageType::class);
        $type1->expects($this->any())
            ->method('getDimensions')
            ->willReturn(['testDimension' => [1, 2, 3]]);

        $allTypes = ['type1' => $type1];
        $this->typeProvider->expects($this->any())
            ->method('getImageTypes')
            ->willReturn($allTypes);

        $this->attachmentManager->expects($this->any())
            ->method('getFilteredImageUrl')
            ->willReturn('testUrl');


        $this->attachmentManager->expects($this->any())
            ->method('isImageType')
            ->willReturn($isImageType);

        $this->repo->expects($this->any())
            ->method('findOneBy')
            ->with(['image' => $initialResults['id']])
            ->willReturn($productImage);

        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setMetadata(new EntityMetadata());
        $this->context->setResult($initialResults);
        $this->processor->process($this->context);

        self::assertEquals($expectedResults, $this->context->getResult());
    }

    public function getTestProcessShouldHandlePathsCorrectlyProvider()
    {
        $basicInitialResults = ['id' => 1, 'content' => 'testContent', 'mimeType' => 'testMime'];
        $productImageType = $this->createMock(ProductImageType::class);
        $productImageType->expects($this->any())
            ->method('getType')
            ->willReturn('type1');
        $productImage = $this->createMock(ProductImageStub::class);
        $productImage->expects($this->any())
            ->method('getTypes')
            ->willReturn([$productImageType]);
        $imageFile = $this->createMock(File::class);
        $productImage->expects($this->any())
            ->method('getImage')
            ->willReturn($imageFile);

        return [
            [
                'initialResults' => $basicInitialResults,
                'isImageType' => false,
                'productImage' => null,
                'expectedResults' => $basicInitialResults,
            ],
            [
                'initialResults' => $basicInitialResults,
                'isImageType' => true,
                'productImage' => $productImage,
                'expectedResults' => array_merge($basicInitialResults, ['filePath' => ['testDimension' => 'testUrl']]),
            ],
        ];
    }
}

<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor\Shared;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Api\Processor\Shared\ProcessImagePaths;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor\Stub\ProductImageStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor\Stub\ContextStub;
use Oro\Component\ChainProcessor\ContextInterface;

class ProcessImagePathsTest extends \PHPUnit_Framework_TestCase
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
    protected $addImagePathToResultsProcessor;

    /**
     * @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var EntityDefinitionConfig|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repo;

    protected function setUp()
    {
        $this->attachmentManager = $this->getMockBuilder(AttachmentManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->repo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($this->repo);

        $this->typeProvider = $this->getMockBuilder(ImageTypeProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->addImagePathToResultsProcessor = new ProcessImagePaths(
            $this->attachmentManager,
            $this->doctrineHelper,
            $this->typeProvider
        );
        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context * */
        $this->context = $this->createMock(ContextStub::class);
        $this->config = $this->createMock(EntityDefinitionConfig::class);
        $this->context->expects($this->once())
            ->method('getConfig')
            ->willReturn($this->config);
    }

    public function testProcessShouldAddFilePathToConfig()
    {
        $this->config->expects($this->once())
            ->method('getKey')
            ->willReturn('testKey');
        $this->config->expects($this->once())
            ->method('addField')
            ->willReturnCallback(
                function ($fieldName, $configValue) {
                    $this->assertEquals(ProcessImagePaths::CONFIG_FILE_PATH, $fieldName);
                    $this->assertInstanceOf(EntityDefinitionFieldConfig::class, $configValue);
                }
            );
        $this->config->expects($this->once())
            ->method('setKey')
            ->willReturnCallback(
                function ($newKey) {
                    $this->assertEquals('testKeynew', $newKey);
                }
            );
        $this->context->expects($this->once())
            ->method('setMetadata');
        $this->context->expects($this->once())
            ->method('getMetadata');
        $this->context->expects($this->once())
            ->method('setConfig');

        $this->addImagePathToResultsProcessor->process($this->context);
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
        $type1 = $this->getMockBuilder(ThemeImageType::class)->disableOriginalConstructor()->getMock();
        $type1->expects($this->any())
            ->method('getDimensions')
            ->willReturn(['testDimension' => [1, 2, 3]]);

        $allTypes = ['type1' => $type1];
        $this->typeProvider->expects($this->any())
            ->method('getImageTypes')
            ->willReturn($allTypes);

        $this->context->expects($this->once())
            ->method('getResult')
            ->willReturn($initialResults);

        $this->attachmentManager->expects($this->any())
            ->method('getFilteredImageUrl')
            ->willReturn('testUrl');


        $this->attachmentManager->expects($this->any())
            ->method('isImageType')
            ->willReturn($isImageType);
        if (array_key_exists('content', $initialResults)) {
            $this->repo->expects($this->any())
                ->method('findOneBy')
                ->with(['image' => $initialResults['id']])
                ->willReturn($productImage);
        } else {
            foreach ($initialResults as $initialResult) {
                $this->repo->expects($this->any())
                    ->method('findOneBy')
                    ->with(['image' => $initialResult['id']])
                    ->willReturn($productImage[$initialResult['id']]);
            }
        }

        $this->context->expects($this->once())
            ->method('setResult')
            ->willReturnCallback(
                function ($results) use ($expectedResults, $initialResults) {
                    $this->assertSame($expectedResults, $results);
                }
            );

        $this->addImagePathToResultsProcessor->process($this->context);

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
            [
                'initialResults' => [$basicInitialResults],
                'isImageType' => true,
                'productImage' => [1 => $productImage],
                'expectedResults' => [array_merge($basicInitialResults, ['filePath' => ['testDimension' => 'testUrl']])],
            ],
            [
                'initialResults' => [$basicInitialResults, $basicInitialResults],
                'isImageType' => true,
                'productImage' => [1 => $productImage],
                'expectedResults' => [
                    array_merge($basicInitialResults, ['filePath' => ['testDimension' => 'testUrl']]),
                    array_merge($basicInitialResults, ['filePath' => ['testDimension' => 'testUrl']]),
                ],
            ],
        ];
    }


}

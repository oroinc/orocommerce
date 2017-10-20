<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor\Shared;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create\CreateProcessorTestCase;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Api\Processor\Shared\ProcessImageTypesCollection;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Helper\ProductImageHelper;

class ProcessImageTypesCollectionTest extends CreateProcessorTestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject $doctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ImageTypeProvider|\PHPUnit_Framework_MockObject_MockObject $imageTypeProvider
     */
    protected $imageTypeProvider;

    /**
     * @var ProductImageHelper|\PHPUnit_Framework_MockObject_MockObject $productImageHelper
     */
    protected $productImageHelper;

    /**
     * @var ProcessImageTypesCollection $processImageTypesCollection
     */
    protected $processImageTypesCollection;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $productImageEntityManager
     */
    protected $productImageEntityManager;

    protected function setUp()
    {
        parent::setUp();

        $this->productImageEntityManager = $this->createMock(EntityManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->imageTypeProvider = $this->createMock(ImageTypeProvider::class);
        $this->productImageHelper = $this->createMock(ProductImageHelper::class);

        $this->processImageTypesCollection =
            new ProcessImageTypesCollection(
                $this->doctrineHelper,
                $this->imageTypeProvider,
                $this->productImageHelper
            );
    }

    public function testProcess()
    {
        $parentProductImage = new ProductImage();
        $parentProductImage->setTypes(
            new ArrayCollection(
                [
                    new ProductImageType('main'),
                    new ProductImageType('listing')
                ]
            )
        );

        $parentProduct = new Product();
        $parentProduct->addImage($parentProductImage);

        $initialResult = new ProductImage();
        $initialResult->addType(new ProductImageType('listing'));
        $initialResult->setProduct($parentProduct);

        $this->context->setResult($initialResult);

        $this->imageTypeProvider->expects($this->once())
            ->method('getMaxNumberByType')
            ->willReturn(
                [
                    'main' => [
                        'max' => 1,
                        'label' => 'Main'
                    ],
                    'listing' => [
                        'max' => 1,
                        'label' => 'Listing'
                    ]
                ]
            );

        $this->productImageHelper->expects($this->once())
            ->method('countImagesByType')
            ->willReturn(
                [
                    'main' => 1,
                    'listing' => 1,
                ]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->willReturn($this->productImageEntityManager);

        $this->productImageEntityManager->expects($this->once())
            ->method('persist')
            ->willReturn(true);

        $this->processImageTypesCollection->process($this->context);

        $this->assertFalse($this->context->hasErrors());
    }
}

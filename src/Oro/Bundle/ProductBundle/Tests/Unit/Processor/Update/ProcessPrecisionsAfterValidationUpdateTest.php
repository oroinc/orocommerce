<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Processor\Update;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormContextStub;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitPrecisionRepository;
use Oro\Bundle\ProductBundle\Processor\Update\ProcessPrecisionsAfterValidationUpdate;

class ProcessPrecisionsAfterValidationUpdateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /** @var SingleItemContext|FormContextStub|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;
    /**
     * @var ProcessPrecisionsAfterValidationUpdate
     */
    protected $processPrecisionsAfterValidationUpdate;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configProvider   = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder(MetadataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context = new FormContextStub($configProvider, $metadataProvider);
        $this->processPrecisionsAfterValidationUpdate = new ProcessPrecisionsAfterValidationUpdate(
            $this->doctrineHelper
        );
    }

    public function testHandleProductUnitPrecisions()
    {
        $this->context->set('addedUnits', [1]);
        $productUnitPrecisionRepo = $this->createMock(ProductUnitPrecisionRepository::class);
        $productUnitPrecisionRepo->expects($this->once())
            ->method('deleteProductUnitPrecisionsById');
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(ProductUnitPrecision::class)
            ->willReturn($productUnitPrecisionRepo);

        $this->processPrecisionsAfterValidationUpdate->handleProductUnitPrecisions($this->context);
    }
}

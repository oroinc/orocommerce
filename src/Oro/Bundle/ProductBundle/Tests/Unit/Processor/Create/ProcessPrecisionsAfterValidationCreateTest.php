<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Processor\Create;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormContextStub;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitPrecisionRepository;
use Oro\Bundle\ProductBundle\Processor\Create\ProcessPrecisionsAfterValidationCreate;
use Oro\Bundle\ProductBundle\Tests\Unit\Processor\Shared\ProcessUnitPrecisionsTestHelper;

class ProcessPrecisionsAfterValidationCreateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /** @var SingleItemContext|FormContextStub|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;
    /**
     * @var ProcessPrecisionsAfterValidationCreate
     */
    protected $processPrecisionsAfterValidationCreate;

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
        $this->processPrecisionsAfterValidationCreate = new ProcessPrecisionsAfterValidationCreate(
            $this->doctrineHelper
        );
    }

    public function testHandleProductUnitPrecisions()
    {
        $requestData = ProcessUnitPrecisionsTestHelper::createNormalizedRequestData();
        $this->context->setRequestData($requestData);
        $productUnitRepo = $this->getMockBuilder(ProductUnitPrecisionRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productUnitRepo->expects($this->once())
            ->method('deleteProductUnitPrecisionsById');
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(ProductUnitPrecision::class)
            ->willReturn($productUnitRepo);

        $this->processPrecisionsAfterValidationCreate->handleProductUnitPrecisions($this->context);
    }
}

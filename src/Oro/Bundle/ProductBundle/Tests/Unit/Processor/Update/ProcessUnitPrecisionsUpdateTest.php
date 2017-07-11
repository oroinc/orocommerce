<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Processor\Update;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApi;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormContextStub;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitPrecisionRepository;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Processor\Shared\ProcessUnitPrecisions;
use Oro\Bundle\ProductBundle\Tests\Unit\Processor\Shared\ProcessUnitPrecisionsTestHelper;
use Oro\Bundle\ProductBundle\Tests\Unit\Processor\Shared\UpdateContextStub;

class ProcessUnitPrecisionsUpdateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /** @var SingleItemContext|FormContextStub|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;
    /**
     * @var ProcessUnitPrecisionsUpdateStub
     */
    protected $processUnitPrecisionsUpdate;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder(MetadataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context = new UpdateContextStub($configProvider, $metadataProvider);
        $this->processUnitPrecisionsUpdate = new ProcessUnitPrecisionsUpdateStub($this->doctrineHelper);
    }

    /**
     *
     * @param $requestData
     * @param $isValid
     */
    public function testValidateUnitPrecisions()
    {
        $unitCodes = ['each', 'item', 'piece', 'set', 'kilogram', 'hour'];
        $productUnitPrecisions = $this->getProductUnitPrecisions(['item', 'set', 'each']);
        $requestData = ProcessUnitPrecisionsTestHelper::createUpdateRequestData();
        $productUnitRepo = $this->getMockBuilder(ProductUnitRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productUnitRepo->expects($this->once())
            ->method('getAllUnitCodes')
            ->willReturn($unitCodes);
        $productUnitPrecisionRepo = $this->getMockBuilder(ProductUnitPrecisionRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productUnitPrecisionRepo->expects($this->once())
            ->method('getProductUnitPrecisionsByProductId')
            ->willReturn($productUnitPrecisions);
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityRepositoryForClass')
            ->withConsecutive([ProductUnit::class], [ProductUnitPrecision::class])
            ->willReturnOnConsecutiveCalls($productUnitRepo, $productUnitPrecisionRepo);
        $pointer = '/' . JsonApi::INCLUDED;
        $this->processUnitPrecisionsUpdate->setContext($this->context);
        $result = $this->processUnitPrecisionsUpdate->validateUnitPrecisions(
            $requestData[JsonApi::INCLUDED],
            $pointer
        );

        $this->assertEquals(true, $result);
    }

    /**
     * @param array $codes
     * @return array
     */
    private function getProductUnitPrecisions($codes)
    {
        $productUnitPrecisions = [];
        $i = 0;
        foreach ($codes as $unitCode) {
            $productUnitPrecisions[] = $this->createProductUnitPrecision(++$i, $unitCode);
        }

        return $productUnitPrecisions;
    }

    /**
     * @param int $id
     * @param string $code
     * @return ProductUnitPrecisionStub
     */
    private function createProductUnitPrecision($id, $code)
    {
        $productUnitPrecision = new ProductUnitPrecisionStub();
        $productUnit = new ProductUnit();
        $product = new Product();
        $productUnit->setCode($code);
        $productUnitPrecision->setId($id);
        $productUnitPrecision->setUnit($productUnit);
        $productUnitPrecision->setProduct($product);
        $productUnitPrecision->setConversionRate(3);
        $productUnitPrecision->setSell(true);

        return $productUnitPrecision;
    }
}

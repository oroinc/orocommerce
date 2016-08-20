<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorFilter;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;

class ComponentProcessorFilterTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_CLASS = 'Oro\Bundle\ProductBundle\Entity\Product';

    /**
     * @var ComponentProcessorFilter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $filter;

    /**
     * @var ProductManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productManager;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $managerRegistry;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $manager;

    /**
     * @var ProductRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productRepository;

    protected function setUp()
    {
        $this->filter = new ComponentProcessorFilter($this->getProductManager(), $this->getManagerRegistry());
        $this->filter->setProductClass(self::PRODUCT_CLASS);
    }

    protected function tearDown()
    {
        unset($this->filter, $this->productManager, $this->managerRegistry, $this->manager, $this->productRepository);
    }

    /**
     * @return ProductManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getProductManager()
    {
        if (!$this->productManager) {
            $this->productManager = $this->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Manager\ProductManager')
                ->disableOriginalConstructor()
                ->getMock();
        }
        return $this->productManager;
    }

    /**
     * @return ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getManagerRegistry()
    {
        if (!$this->managerRegistry) {
            $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
            $this->managerRegistry->expects($this->any())
                ->method('getManagerForClass')
                ->with(self::PRODUCT_CLASS)
                ->willReturn($this->getManager());
        }
        return $this->managerRegistry;
    }

    /**
     * @return ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getManager()
    {
        if (!$this->manager) {
            $this->manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
            $this->manager->expects($this->once())
                ->method('getRepository')
                ->with(self::PRODUCT_CLASS)
                ->willReturn($this->getProductRepository());
        }
        return $this->manager;
    }

    /**
     * @return ProductRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getProductRepository()
    {
        if (!$this->productRepository) {
            $this->productRepository = $this
                ->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository')
                ->disableOriginalConstructor()
                ->getMock();
        }
        return $this->productRepository;
    }

    public function testFilterData()
    {
        $skus = ['visibleSku1', 'invisibleSku1', 'visibleSku2'];
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                ['productSku' => $skus[0]],
                ['productSku' => $skus[1]],
                ['productSku' => $skus[2]],
            ],
        ];
        $dataParameters = [];

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getResult')
            ->willReturnCallback(function () use ($skus) {
                $filteredSkus = [];
                foreach ($skus as $sku) {
                    if (strpos($sku, 'invisibleSku') === false) {
                        $filteredSkus[] = ['sku' => $sku];
                    }
                }
                return $filteredSkus;
            });

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->getProductRepository()->expects($this->once())
            ->method('getFilterSkuQueryBuilder')
            ->with(array_map('strtoupper', $skus))
            ->willReturn($queryBuilder);

        $this->getProductManager()->expects($this->once())
            ->method('restrictQueryBuilder')
            ->with($queryBuilder, $dataParameters)
            ->willReturn($queryBuilder);

        $filteredData = $this->filter->filterData($data, $dataParameters);

        $this->assertInternalType('array', $filteredData);
        $this->assertCount(2, $filteredData[ProductDataStorage::ENTITY_ITEMS_DATA_KEY]);
        $this->assertEquals(
            $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY][0],
            $filteredData[ProductDataStorage::ENTITY_ITEMS_DATA_KEY][0]
        );
        $this->assertEquals(
            $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY][2],
            $filteredData[ProductDataStorage::ENTITY_ITEMS_DATA_KEY][1]
        );
    }
}

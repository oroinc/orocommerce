<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\PricingBundle\EventListener\AbstractProductPriceDatagridListener;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;

abstract class AbstractProductPriceDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractProductPriceDatagridListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PriceListRequestHandler
     */
    protected $priceListRequestHandler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->expects($this->any())
            ->method('trans')
            ->with($this->isType('string'))
            ->willReturnCallback(
                function ($id, array $params = []) {
                    $id = str_replace(array_keys($params), array_values($params), $id);

                    return $id . '.trans';
                }
            );

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceListRequestHandler = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = $this->createListener();

        $this->listener->setProductPriceClass('OroB2BPricingBundle:ProductPrice');
        $this->listener->setProductUnitClass('OroB2BProductBundle:ProductUnit');
    }

    /**
     * @return AbstractProductPriceDatagridListener
     */
    protected function createListener()
    {
        $className = $this->getListenerClassName();
        return new $className(
            $this->translator,
            $this->doctrineHelper,
            $this->priceListRequestHandler
        );
    }

    /**
     * @return string
     */
    abstract protected function getListenerClassName();

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->doctrineHelper, $this->translator, $this->priceListRequestHandler, $this->listener);
    }

    public function testSetProductPriceClass()
    {
        $listener = $this->createListener();
        $this->assertNull($this->getProperty($listener, 'productPriceClass'));
        $listener->setProductPriceClass('OroB2BPricingBundle:ProductPrice');
        $this->assertEquals(
            'OroB2BPricingBundle:ProductPrice',
            $this->getProperty($listener, 'productPriceClass')
        );
    }

    public function testSetProductUnitClass()
    {
        $listener = $this->createListener();
        $this->assertNull($this->getProperty($listener, 'productUnitClass'));
        $listener->setProductUnitClass('OroB2BProductBundle:ProductUnit');
        $this->assertEquals(
            'OroB2BProductBundle:ProductUnit',
            $this->getProperty($listener, 'productUnitClass')
        );
    }

    /**
     * @param int|null $priceListId
     * @param array $priceCurrencies
     * @param array $expectedConfig
     * @dataProvider onBuildBeforeDataProvider
     */
    public function testOnBuildBefore($priceListId = null, array $priceCurrencies = [], array $expectedConfig = [])
    {
        $this->getRepository();
        $this->setUpPriceListRequestHandler($priceListId, $priceCurrencies);

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config = DatagridConfiguration::create([]);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);

        $this->assertEquals($expectedConfig, $config->toArray());
    }

    /**
     * @param int|null $priceListId
     * @param array $priceCurrencies
     */
    abstract protected function setUpPriceListRequestHandler($priceListId = null, array $priceCurrencies = []);

    /**
     * @param int|null $priceListId
     * @param array $priceCurrencies
     * @param array $sourceResults
     * @param ProductPrice[] $prices
     * @param array $expectedResults
     * @dataProvider onResultAfterDataProvider
     */
    public function testOnResultAfter(
        $priceListId = null,
        array $priceCurrencies = [],
        array $sourceResults = [],
        array $prices = [],
        array $expectedResults = []
    ) {
        $sourceResultRecords = [];
        $productIds = [];
        foreach ($sourceResults as $sourceResult) {
            $sourceResultRecords[] = new ResultRecord($sourceResult);
            $productIds[] = $sourceResult['id'];
        }

        $this->setUpPriceListRequestHandler($priceListId, $priceCurrencies);

        if ($priceListId && $priceCurrencies) {
            $this->priceListRequestHandler->expects($this->any())->method('getShowTierPrices')->willReturn(true);

            $this->getRepository()
                ->expects($this->any())
                ->method('findByPriceListIdAndProductIds')
                ->with($priceListId, $productIds)
                ->willReturn($prices);
        }

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new OrmResultAfter($datagrid, $sourceResultRecords);
        $this->listener->onResultAfter($event);
        $actualResults = $event->getRecords();

        $this->assertSameSize($expectedResults, $actualResults);
        foreach ($expectedResults as $key => $expectedResult) {
            $actualResult = $actualResults[$key];
            foreach ($expectedResult as $name => $value) {
                $this->assertEquals($value, $actualResult->getValue($name));
            }
        }
    }

    /**
     * @param int $id
     * @return PriceList
     */
    protected function getPriceList($id)
    {
        $priceList = new PriceList();
        $reflection = new \ReflectionProperty(get_class($priceList), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($priceList, $id);

        return $priceList;
    }

    /**
     * @param int $productId
     * @param float $value
     * @param string $currency
     * @param ProductUnit|null $unit
     * @return ProductPrice
     */
    protected function createPrice($productId, $value, $currency, $unit = null)
    {
        $product = new Product();

        $reflection = new \ReflectionProperty(get_class($product), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($product, $productId);

        $price = new ProductPrice();
        $price->setProduct($product)
            ->setPrice(Price::create($value, $currency));
        if ($unit) {
            $price->setUnit($unit);
        }

        return $price;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ProductPriceRepository
     */
    protected function getRepository()
    {
        $repository = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->any())
            ->method('findBy')
            ->willReturn([$this->getUnit('unit1')]);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->withConsecutive(['OroB2BProductBundle:ProductUnit'], ['OroB2BPricingBundle:ProductPrice'])
            ->willReturn($repository);

        return $repository;
    }

    /**
     * @param object $object
     * @param string $property
     * @return mixed $value
     */
    protected function getProperty($object, $property)
    {
        $reflection = new \ReflectionProperty(get_class($object), $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }

    /**
     * @param string $unitCode
     * @return ProductUnit
     */
    protected function getUnit($unitCode)
    {
        return (new ProductUnit())->setCode($unitCode);
    }
}

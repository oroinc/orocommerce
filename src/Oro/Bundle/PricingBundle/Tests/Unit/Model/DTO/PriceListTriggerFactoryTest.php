<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListProductsTrigger;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListTrigger;
use Oro\Bundle\PricingBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Testing\Unit\EntityTrait;

class PriceListTriggerFactoryTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var PriceListTriggerFactory
     */
    protected $priceRuleTriggerFactory;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->priceRuleTriggerFactory = new PriceListTriggerFactory($this->registry);
    }

    public function testCreate()
    {
        /** @var PriceList|\PHPUnit_Framework_MockObject_MockObject $priceList **/
        $priceList = $this->createMock(PriceList::class);

        /** @var Product|\PHPUnit_Framework_MockObject_MockObject $product **/
        $product = $this->createMock(Product::class);

        $trigger = $this->priceRuleTriggerFactory->create($priceList, [$product]);
        $this->assertInstanceOf(PriceListTrigger::class, $trigger);
        $this->assertSame($priceList, $trigger->getPriceList());
        $this->assertSame([$product], $trigger->getProducts());
    }

    public function testCreateWithoutPriceList()
    {
        /** @var Product|\PHPUnit_Framework_MockObject_MockObject $product **/
        $product = $this->createMock(Product::class);

        $trigger = $this->priceRuleTriggerFactory->createWithoutPriceList([$product]);
        $this->assertInstanceOf(PriceListTrigger::class, $trigger);
        $this->assertNull($trigger->getPriceList());
        $this->assertSame([$product], $trigger->getProducts());
    }

    public function testTriggerToArray()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 2]);
        $trigger = new PriceListTrigger($priceList, [$product]);

        $expected = [
            PriceListTriggerFactory::PRICE_LIST => 1,
            PriceListTriggerFactory::PRODUCT => [2]
        ];
        $this->assertSame($expected, $this->priceRuleTriggerFactory->triggerToArray($trigger));
    }

    public function testCreateFromIds()
    {
        $this->assertSame(
            [
                PriceListTriggerFactory::PRICE_LIST => 1001,
                PriceListTriggerFactory::PRODUCT => [2002]
            ],
            $this->priceRuleTriggerFactory->createFromIds(1001, [2002])
        );
    }

    public function testCreateFromIdsWithoutPriceList()
    {
        $this->assertSame(
            [
                PriceListTriggerFactory::PRODUCT => [1001 => [2002]]
            ],
            $this->priceRuleTriggerFactory->createFromIds(
                null,
                [1001 => [$this->getEntity(Product::class, ['id' => 2002])]]
            )
        );
    }

    public function testCreateFromArray()
    {
        $data = [
            PriceListTriggerFactory::PRICE_LIST => 1,
            PriceListTriggerFactory::PRODUCT => [2]
        ];

        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $productId = 2;

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(PriceList::class, 1)
            ->willReturn($priceList);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $trigger = $this->priceRuleTriggerFactory->createFromArray($data);
        $this->assertInstanceOf(PriceListTrigger::class, $trigger);
        $this->assertSame($priceList, $trigger->getPriceList());
        $this->assertSame([$productId], $trigger->getProducts());
    }

    public function testCreateFromArrayInvalidData()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message should not be empty.');
        $this->priceRuleTriggerFactory->createFromArray(null);
    }

    public function testCreateFromArrayNoPriceList()
    {
        $data = [
            PriceListTriggerFactory::PRODUCT => [1 => [2]]
        ];

        $this->registry->expects($this->never())
            ->method('getManagerForClass');

        $result = $this->priceRuleTriggerFactory->createFromArray($data);
        $this->assertInstanceOf(PriceListTrigger::class, $result);
        $this->assertInstanceOf(PriceListProductsTrigger::class, $result);
        $this->assertNull($result->getPriceList());
        $this->assertSame($data[PriceListTriggerFactory::PRODUCT], $result->getProducts());
    }
}

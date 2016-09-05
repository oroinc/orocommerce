<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PricingBundle\Entity\PriceList;
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
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->priceRuleTriggerFactory = new PriceListTriggerFactory($this->registry);
    }

    public function testCreate()
    {
        /** @var PriceList|\PHPUnit_Framework_MockObject_MockObject $priceList **/
        $priceList = $this->getMock(PriceList::class);

        /** @var Product|\PHPUnit_Framework_MockObject_MockObject $product **/
        $product = $this->getMock(Product::class);

        $trigger = $this->priceRuleTriggerFactory->create($priceList, $product);
        $this->assertInstanceOf(PriceListTrigger::class, $trigger);
        $this->assertSame($priceList, $trigger->getPriceList());
        $this->assertSame($product, $trigger->getProduct());
    }

    public function testTriggerToArray()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 2]);
        $trigger = new PriceListTrigger($priceList, $product);

        $expected = [
            PriceListTriggerFactory::PRICE_LIST => 1,
            PriceListTriggerFactory::PRODUCT => 2
        ];
        $this->assertSame($expected, $this->priceRuleTriggerFactory->triggerToArray($trigger));
    }

    public function testCreateFromArray()
    {
        $data = [
            PriceListTriggerFactory::PRICE_LIST => 1,
            PriceListTriggerFactory::PRODUCT => 2
        ];
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 2]);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                [PriceList::class, 1],
                [Product::class, 2]
            )
            ->willReturnMap(
                [
                    [PriceList::class, 1, $priceList],
                    [Product::class, 2, $product]
                ]
            );

        $this->registry->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturn($em);

        $trigger = $this->priceRuleTriggerFactory->createFromArray($data);
        $this->assertInstanceOf(PriceListTrigger::class, $trigger);
        $this->assertSame($priceList, $trigger->getPriceList());
        $this->assertSame($product, $trigger->getProduct());
    }

    public function testCreateFromArrayInvalidData()
    {
        $this->setExpectedException(InvalidArgumentException::class, 'Message should not be empty.');
        $this->priceRuleTriggerFactory->createFromArray(null);
    }

    public function testCreateFromArrayNoPriceList()
    {
        $this->setExpectedException(InvalidArgumentException::class, 'Price List is required.');

        $data = [
            PriceListTriggerFactory::PRICE_LIST => 1,
            PriceListTriggerFactory::PRODUCT => 2
        ];

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(PriceList::class, 1)
            ->willReturn(null);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->priceRuleTriggerFactory->createFromArray($data);
    }
}

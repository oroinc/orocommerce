<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Model\DTO;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Model\Exception\InvalidArgumentException;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\PricingBundle\Model\DTO\PriceRuleTrigger;
use OroB2B\Bundle\PricingBundle\Model\DTO\PriceRuleTriggerFactory;

class PriceRuleTriggerFactoryTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var PriceRuleTriggerFactory
     */
    protected $priceRuleTriggerFactory;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->priceRuleTriggerFactory = new PriceRuleTriggerFactory($this->registry);
    }

    public function testCreate()
    {
        /** @var PriceList|\PHPUnit_Framework_MockObject_MockObject $priceList **/
        $priceList = $this->getMock(PriceList::class);

        /** @var Product|\PHPUnit_Framework_MockObject_MockObject $product **/
        $product = $this->getMock(Product::class);

        $trigger = $this->priceRuleTriggerFactory->create($priceList, $product);
        $this->assertInstanceOf(PriceRuleTrigger::class, $trigger);
        $this->assertSame($priceList, $trigger->getPriceList());
        $this->assertSame($product, $trigger->getProduct());
    }

    public function testTriggerToArray()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 2]);
        $trigger = new PriceRuleTrigger($priceList, $product);

        $expected = [
            PriceRuleTriggerFactory::PRICE_LIST => 1,
            PriceRuleTriggerFactory::PRODUCT => 2
        ];
        $this->assertSame($expected, $this->priceRuleTriggerFactory->triggerToArray($trigger));
    }

    public function testCreateFromArray()
    {
        $data = [
            PriceRuleTriggerFactory::PRICE_LIST => 1,
            PriceRuleTriggerFactory::PRODUCT => 2
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
        $this->assertInstanceOf(PriceRuleTrigger::class, $trigger);
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
            PriceRuleTriggerFactory::PRICE_LIST => 1,
            PriceRuleTriggerFactory::PRODUCT => 2
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

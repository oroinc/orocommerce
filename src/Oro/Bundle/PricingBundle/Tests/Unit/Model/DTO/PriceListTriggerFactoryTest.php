<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

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
     * @var PriceListTriggerFactory
     */
    protected $priceRuleTriggerFactory;

    protected function setUp()
    {
        $this->priceRuleTriggerFactory = new PriceListTriggerFactory();
    }

    public function testCreate()
    {
        /** @var Product|\PHPUnit_Framework_MockObject_MockObject $product **/
        $product = $this->getEntity(Product::class, ['id' => 2002]);

        $trigger = $this->priceRuleTriggerFactory->create([1001 => [$product]]);

        $this->assertInstanceOf(PriceListTrigger::class, $trigger);
        $this->assertSame([1001 => [$product]], $trigger->getProducts());
    }

    public function testTriggerToArray()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 2002]);

        $trigger = new PriceListTrigger([1001 => [$product]]);

        $this->assertSame(
            [
                PriceListTriggerFactory::PRODUCT => [1001 => [2002]]
            ],
            $this->priceRuleTriggerFactory->triggerToArray($trigger)
        );
    }

    public function testCreateFromIds()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 2002]);

        $this->assertSame(
            [
                PriceListTriggerFactory::PRODUCT => [1001 => [2002]]
            ],
            $this->priceRuleTriggerFactory->createFromIds([1001 => [$product]])
        );
    }

    public function testCreateFromArrayInvalidData()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message should not be empty.');
        $this->priceRuleTriggerFactory->createFromArray(null);
    }

    public function testCreateFromArray()
    {
        $data = [
            PriceListTriggerFactory::PRODUCT => [1001 => [2002]]
        ];

        $result = $this->priceRuleTriggerFactory->createFromArray($data);

        $this->assertInstanceOf(PriceListTrigger::class, $result);
        $this->assertSame($data[PriceListTriggerFactory::PRODUCT], $result->getProducts());
    }
}

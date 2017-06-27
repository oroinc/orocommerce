<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Converter;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Oro\Bundle\PromotionBundle\Discount\Converter\LineItemsToDiscountLineItemsConverter;
use Oro\Bundle\PromotionBundle\Discount\Converter\ShoppingListDiscountContextConverter;
use Oro\Component\Testing\Unit\EntityTrait;

class ShoppingListDiscountContextConverterTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ShoppingListTotalManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shoppingListTotalManager;

    /**
     * @var LineItemsToDiscountLineItemsConverter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $lineItemsConverter;

    /**
     * @var ShoppingListDiscountContextConverter
     */
    protected $converter;

    protected function setUp()
    {
        $this->shoppingListTotalManager = $this->createMock(ShoppingListTotalManager::class);
        $this->lineItemsConverter = $this->createMock(LineItemsToDiscountLineItemsConverter::class);

        $this->converter = new ShoppingListDiscountContextConverter(
            $this->shoppingListTotalManager,
            $this->lineItemsConverter
        );
    }

    public function testConvert()
    {
        $sourceEntity = new ShoppingList();
        $sourceEntity->setSubtotal((new Subtotal())->setAmount(100));
        /** @var LineItem $lineItem */
        $lineItem = $this->getEntity(LineItem::class, ['id' => 42]);
        $sourceEntity->addLineItem($lineItem);

        $this->shoppingListTotalManager->expects($this->once())
            ->method('setSubtotals')
            ->with([$sourceEntity], false);

        $discountLineItems = [
            (new DiscountLineItem())->setSubtotal(100)
        ];

        $this->lineItemsConverter->expects($this->once())
            ->method('convert')
            ->with([$lineItem])
            ->willReturn($discountLineItems);

        $expectedDiscountContext = new DiscountContext();
        $expectedDiscountContext->setSubtotal(100);
        $expectedDiscountContext->setLineItems($discountLineItems);

        $this->assertEquals($expectedDiscountContext, $this->converter->convert($sourceEntity));
    }

    public function testConvertUnsupportedException()
    {
        $entity = new \stdClass();
        $this->expectException(UnsupportedSourceEntityException::class);
        $this->expectExceptionMessage('Source entity "stdClass" is not supported.');

        $this->converter->convert($entity);
    }

    /**
     * @dataProvider supportsDataProvider
     * @param object $entity
     * @param boolean $isSupported
     */
    public function testSupports($entity, $isSupported)
    {
        $this->assertSame($isSupported, $this->converter->supports($entity));
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        return [
            'supported entity' => [
                'entity' => new ShoppingList(),
                'isSupported' => true
            ],
            'not supported entity' => [
                'entity' => new \stdClass(),
                'isSupported' => false
            ],
        ];
    }
}

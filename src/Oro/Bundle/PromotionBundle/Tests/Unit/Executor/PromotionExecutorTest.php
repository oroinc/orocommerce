<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Executor;

use Oro\Bundle\PromotionBundle\Discount\Converter\DiscountContextConverterInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyInterface;
use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyProvider;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\PromotionBundle\Provider\PromotionDiscountsProviderInterface;

class PromotionExecutorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DiscountContextConverterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $discountContextConverter;

    /**
     * @var StrategyProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $discountStrategyProvider;

    /**
     * @var PromotionDiscountsProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $promotionDiscountsProvider;

    /**
     * @var PromotionExecutor
     */
    private $executor;

    protected function setUp()
    {
        $this->discountContextConverter = $this->createMock(DiscountContextConverterInterface::class);
        $this->discountStrategyProvider = $this->createMock(StrategyProvider::class);
        $this->promotionDiscountsProvider = $this->createMock(PromotionDiscountsProviderInterface::class);

        $this->executor = new PromotionExecutor(
            $this->discountContextConverter,
            $this->discountStrategyProvider,
            $this->promotionDiscountsProvider
        );
    }

    public function testExecuteNoDiscounts()
    {
        $sourceEntity = new \stdClass();
        $discountContext = new DiscountContext();

        $this->discountContextConverter->expects($this->once())
            ->method('convert')
            ->with($sourceEntity)
            ->willReturn($discountContext);

        $this->promotionDiscountsProvider->expects($this->once())
            ->method('getDiscounts')
            ->with($sourceEntity, $discountContext)
            ->willReturn([]);

        $this->discountStrategyProvider->expects($this->never())
            ->method($this->anything());

        $this->assertSame($discountContext, $this->executor->execute($sourceEntity));
    }

    public function testExecute()
    {
        $sourceEntity = new \stdClass();
        $discountContext = new DiscountContext();

        $this->discountContextConverter->expects($this->once())
            ->method('convert')
            ->with($sourceEntity)
            ->willReturn($discountContext);

        $discounts = [$this->createMock(DiscountInterface::class), $this->createMock(DiscountInterface::class)];

        $this->promotionDiscountsProvider->expects($this->once())
            ->method('getDiscounts')
            ->with($sourceEntity, $discountContext)
            ->willReturn($discounts);

        $strategy = $this->createMock(StrategyInterface::class);
        $this->discountStrategyProvider->expects($this->once())
            ->method('getActiveStrategy')
            ->willReturn($strategy);

        $modifiedContext = new DiscountContext();
        $strategy->expects($this->once())
            ->method('process')
            ->with($discountContext, $discounts)
            ->willReturn($modifiedContext);

        $this->assertSame($modifiedContext, $this->executor->execute($sourceEntity));
    }

    /**
     * @dataProvider trueFalseDataProvider
     * @param bool $result
     */
    public function testSupports($result)
    {
        $entity = new \stdClass();
        $this->discountContextConverter->expects($this->once())
            ->method('supports')
            ->willReturn($result);

        $this->assertSame($result, $this->executor->supports($entity));
    }

    /**
     * @return array
     */
    public function trueFalseDataProvider()
    {
        return [
            [true],
            [false]
        ];
    }
}

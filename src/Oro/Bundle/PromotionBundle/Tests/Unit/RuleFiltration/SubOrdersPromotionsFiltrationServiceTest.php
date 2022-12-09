<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Context\OrderContextDataConverter;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\RuleFiltration\SubOrdersPromotionsFiltrationService;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;

class SubOrdersPromotionsFiltrationServiceTest extends AbstractSkippableFiltrationServiceTest
{
    private RuleFiltrationServiceInterface|MockObject  $filtrationService;
    private ContextDataConverterInterface|MockObject  $contextDataConverter;
    private SubOrdersPromotionsFiltrationService $subOrdersFiltrationService;

    protected function setUp(): void
    {
        $this->filtrationService = $this->createMock(RuleFiltrationServiceInterface::class);
        $this->contextDataConverter = $this->createMock(ContextDataConverterInterface::class);

        $this->subOrdersFiltrationService = new SubOrdersPromotionsFiltrationService(
            $this->filtrationService,
            $this->contextDataConverter
        );
    }

    public function testFilterRuleOwners()
    {
        $promotion1 = new Promotion();
        ReflectionUtil::setId($promotion1, 1);

        $promotion2 = new Promotion();
        ReflectionUtil::setId($promotion2, 2);

        $subOrder1 = new Order();
        $subOrder2 = new Order();

        $context = [OrderContextDataConverter::SUB_ORDERS => [$subOrder1, $subOrder2]];
        $this->contextDataConverter->expects($this->exactly(2))
            ->method('getContextData')
            ->willReturn([]);

        $this->filtrationService->expects($this->exactly(2))
            ->method('getFilteredRuleOwners')
            ->willReturnOnConsecutiveCalls(
                [$promotion1, $promotion2],
                [$promotion1]
            );

        $promotions = $this->subOrdersFiltrationService->getFilteredRuleOwners([$promotion1, $promotion2], $context);

        $this->assertCount(2, $promotions);
        $this->assertContains($promotion1, $promotions);
        $this->assertContains($promotion2, $promotions);
    }

    /**
     * @param array $context
     * @dataProvider getTestFilterRuleOwnersWithoutSubOrdersData
     */
    public function testFilterRuleOwnersWithoutSubOrders(array $context)
    {
        $promotion1 = new Promotion();
        ReflectionUtil::setId($promotion1, 1);

        $promotion2 = new Promotion();
        ReflectionUtil::setId($promotion2, 2);

        $this->contextDataConverter->expects($this->never())
            ->method('getContextData');

        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->willReturn([$promotion2]);

        $promotions = $this->subOrdersFiltrationService->getFilteredRuleOwners([$promotion1, $promotion2], $context);
        $this->assertCount(1, $promotions);
        $this->assertContains($promotion2, $promotions);
    }

    public function getTestFilterRuleOwnersWithoutSubOrdersData()
    {
        return [
            'Context without subOrders param' => [
                'context' => []
            ],
            'Context with empty subOrders params' => [
                'context' => [OrderContextDataConverter::SUB_ORDERS => []]
            ]
        ];
    }
}

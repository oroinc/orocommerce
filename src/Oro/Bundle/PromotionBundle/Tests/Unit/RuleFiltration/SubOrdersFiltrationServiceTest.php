<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Context\OrderContextDataConverter;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\RuleFiltration\SubOrdersFiltrationService;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Component\Testing\ReflectionUtil;

class SubOrdersFiltrationServiceTest extends \PHPUnit\Framework\TestCase
{
    /** @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $baseFiltrationService;

    /** @var ContextDataConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $contextDataConverter;

    /** @var SubOrdersFiltrationService  */
    private $filtrationService;

    #[\Override]
    protected function setUp(): void
    {
        $this->baseFiltrationService = $this->createMock(RuleFiltrationServiceInterface::class);
        $this->contextDataConverter = $this->createMock(ContextDataConverterInterface::class);

        $this->filtrationService = new SubOrdersFiltrationService(
            $this->baseFiltrationService,
            $this->contextDataConverter
        );
    }

    private function getPromotion(int $id): Promotion
    {
        $promotion = new Promotion();
        ReflectionUtil::setId($promotion, $id);

        return $promotion;
    }

    public function testShouldBeSkippable(): void
    {
        $ruleOwners = [$this->createMock(RuleOwnerInterface::class)];

        $this->baseFiltrationService->expects(self::never())
            ->method('getFilteredRuleOwners');

        self::assertSame(
            $ruleOwners,
            $this->filtrationService->getFilteredRuleOwners(
                $ruleOwners,
                ['skip_filters' => [SubOrdersFiltrationService::class => true]]
            )
        );
    }

    public function testFilterRuleOwners(): void
    {
        $promotion1 = $this->getPromotion(1);
        $promotion2 = $this->getPromotion(2);
        $promotion3 = $this->getPromotion(3);
        $ruleOwners = [$promotion1, $promotion2, $promotion3];

        $subOrder1 = new Order();
        $subOrder2 = new Order();

        $context = [OrderContextDataConverter::SUB_ORDERS => [$subOrder1, $subOrder2]];
        $this->contextDataConverter->expects(self::exactly(2))
            ->method('getContextData')
            ->withConsecutive([self::identicalTo($subOrder1)], [self::identicalTo($subOrder2)])
            ->willReturnOnConsecutiveCalls(
                ['skip_filters' => ['SomeFilter' => true]],
                ['key' => 'val']
            );

        $this->baseFiltrationService->expects(self::exactly(2))
            ->method('getFilteredRuleOwners')
            ->withConsecutive(
                [$ruleOwners, ['skip_filters' => ['SomeFilter' => true]]],
                [$ruleOwners, ['key' => 'val', 'skip_filters' => []]]
            )
            ->willReturnOnConsecutiveCalls(
                [$promotion1, $promotion2],
                [$promotion1]
            );

        $promotions = $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context);
        self::assertSame([$promotion1, $promotion2], $promotions);
    }

    /**
     * @dataProvider getTestFilterRuleOwnersWithoutSubOrdersData
     */
    public function testFilterRuleOwnersWithoutSubOrders(array $context): void
    {
        $promotion1 = $this->getPromotion(1);
        $promotion2 = $this->getPromotion(2);
        $ruleOwners = [$promotion1, $promotion2];
        $filteredRuleOwners = [$promotion2];

        $this->contextDataConverter->expects(self::never())
            ->method('getContextData');

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with($ruleOwners, $context)
            ->willReturn($filteredRuleOwners);

        $promotions = $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context);
        self::assertSame($filteredRuleOwners, $promotions);
    }

    public function getTestFilterRuleOwnersWithoutSubOrdersData(): array
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

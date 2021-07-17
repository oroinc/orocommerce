<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\RuleFiltration\CurrencyFiltrationService;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class CurrencyFiltrationServiceTest extends AbstractSkippableFiltrationServiceTest
{
    /**
     * @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $filtrationService;

    /**
     * @var CurrencyFiltrationService
     */
    protected $currencyFiltrationService;

    protected function setUp(): void
    {
        $this->filtrationService = $this->createMock(RuleFiltrationServiceInterface::class);

        $this->currencyFiltrationService = new CurrencyFiltrationService(
            $this->filtrationService
        );
    }

    /**
     * @dataProvider getFilteredRuleOwnersDataProvider
     */
    public function testGetFilteredRuleOwners(array $context, array $ruleOwners, array $expected)
    {
        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with($expected, $context)
            ->willReturn($expected);

        $this->currencyFiltrationService->getFilteredRuleOwners($ruleOwners, $context);
    }

    public function getFilteredRuleOwnersDataProvider(): array
    {
        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion->expects($this->any())
            ->method('getDiscountConfiguration')
            ->willReturn((new DiscountConfiguration())
                ->setOptions([
                    AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'EUR'
                ]));

        $promotionWithPercentTypeDiscount = $this->createMock(PromotionDataInterface::class);
        $promotionWithPercentTypeDiscount->expects($this->any())
            ->method('getDiscountConfiguration')
            ->willReturn((new DiscountConfiguration())
                ->setOptions([
                    AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_PERCENT,
                ]));

        $promotionWithAnotherCurrencyDiscount =  $this->createMock(PromotionDataInterface::class);
        $promotionWithAnotherCurrencyDiscount->expects($this->any())
            ->method('getDiscountConfiguration')
            ->willReturn((new DiscountConfiguration())
                ->setOptions([
                    AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD'
                ]));

        return [
            'Applicable promotion' => [
                'context' => ['currency' => 'EUR'],
                'ruleOwners' => [$promotion],
                'expected' => [$promotion]
            ],
            'Promotion with percent type discount' => [
                'context' => ['currency' => 'EUR'],
                'ruleOwners' => [$promotionWithPercentTypeDiscount],
                'expected' => [$promotionWithPercentTypeDiscount]
            ],
            'Promotion with another currency discount' => [
                'context' => ['currency' => 'EUR'],
                'ruleOwners' => [$promotionWithAnotherCurrencyDiscount],
                'expected' => []
            ],
            'Several rule owners' => [
                'context' => ['currency' => 'EUR'],
                'ruleOwners' => [
                    $promotion,
                    $promotionWithPercentTypeDiscount,
                    $promotionWithAnotherCurrencyDiscount
                ],
                'expected' => [
                    $promotion,
                    $promotionWithPercentTypeDiscount
                ]
            ]
        ];
    }

    public function testFilterIsSkippable()
    {
        $this->assertServiceSkipped($this->currencyFiltrationService, $this->filtrationService);
    }
}

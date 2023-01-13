<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Placeholder;

use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Placeholder\DiscountInformationPlaceholderFilter;

class DiscountInformationPlaceholderFilterTest extends \PHPUnit\Framework\TestCase
{
    private DiscountInformationPlaceholderFilter $filter;

    protected function setUp(): void
    {
        $this->filter = new DiscountInformationPlaceholderFilter();
    }

    /**
     * @dataProvider isApplicableProvider
     */
    public function testIsApplicable(Promotion $promotion, string $type, bool $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->filter->isApplicable($promotion, $type));
    }

    public function isApplicableProvider(): array
    {
        return [
            'is applicable false' => [
                'promotion' => (new Promotion())
                    ->setDiscountConfiguration(
                        (new DiscountConfiguration())->setType('some type')
                    ),
                'type' => 'some another type',
                'expectedResult' => false,
            ],
            'is applicable true' => [
                'promotion' => (new Promotion())
                    ->setDiscountConfiguration(
                        (new DiscountConfiguration())->setType('some type')
                    ),
                'type' => 'some type',
                'expectedResult' => true,
            ],
        ];
    }
}

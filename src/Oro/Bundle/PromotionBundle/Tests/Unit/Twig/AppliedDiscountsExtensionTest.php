<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Twig;

use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Twig\AppliedDiscountsExtension;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AppliedDiscountsExtensionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const CURRENCY_CODE = 'USD';
    const DISCOUNT_TYPE = 'line_item';
    const FIRST_NAME = 'First promotion name';
    const SECOND_NAME = 'Second promotion name';

    /**
     * @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $container;

    /**
     * @var AppliedDiscountsExtension
     */
    private $appliedDiscountsExtension;

    protected function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->appliedDiscountsExtension = new AppliedDiscountsExtension($this->container);
    }

    public function testGetFunctions()
    {
        $extensionFunctions = $this->appliedDiscountsExtension->getFunctions();
        static::assertCount(1, $extensionFunctions);
        static::assertInstanceOf(\Twig_SimpleFunction::class, reset($extensionFunctions));
    }

    public function testPrepareAppliedDiscounts()
    {
        $firstAppliedDiscount = $this->getEntity(AppliedDiscount::class, [
            'promotionName' => self::FIRST_NAME,
            'currency' => self::CURRENCY_CODE,
            'amount' => 17.0,
            'type' => self::DISCOUNT_TYPE,
            'source_promotion_id' => 5
        ]);

        $secondAppliedDiscount = $this->getEntity(AppliedDiscount::class, [
            'promotionName' => self::SECOND_NAME,
            'currency' => self::CURRENCY_CODE,
            'amount' => 35.0,
            'type' => self::DISCOUNT_TYPE,
            'source_promotion_id' => 6
        ]);

        $expectedItems = [
            [
                'couponCode' => null,
                'promotionName' => self::FIRST_NAME,
                'promotionId' => 5,
                'enabled' => true,
                'amount' => 17.0,
                'currency' => self::CURRENCY_CODE,
                'type' => self::DISCOUNT_TYPE
            ],
            [
                'couponCode' => null,
                'promotionName' => self::SECOND_NAME,
                'promotionId' => 6,
                'enabled' => true,
                'amount' => 35.0,
                'currency' => self::CURRENCY_CODE,
                'type' => self::DISCOUNT_TYPE
            ]
        ];

        $this->assertEquals(
            $expectedItems,
            $this->appliedDiscountsExtension->prepareAppliedDiscounts([$firstAppliedDiscount, $secondAppliedDiscount])
        );
    }

    public function testPrepareAppliedDiscountsWithGrouping()
    {
        $firstAppliedDiscount = $this->getEntity(AppliedDiscount::class, [
            'promotionName' => self::FIRST_NAME,
            'currency' => self::CURRENCY_CODE,
            'amount' => 17.0,
            'type' => self::DISCOUNT_TYPE,
            'source_promotion_id' => 5
        ]);

        $secondAppliedDiscount = $this->getEntity(AppliedDiscount::class, [
            'currency' => self::CURRENCY_CODE,
            'amount' => 35.0,
            'type' => self::DISCOUNT_TYPE,
            'source_promotion_id' => 5
        ]);

        $expectedItems = [
            [
                'couponCode' => null,
                'promotionName' => self::FIRST_NAME,
                'promotionId' => 5,
                'enabled' => true,
                'amount' => 52.0,
                'currency' => self::CURRENCY_CODE,
                'type' => self::DISCOUNT_TYPE
            ],
        ];

        $this->assertEquals(
            $expectedItems,
            $this->appliedDiscountsExtension->prepareAppliedDiscounts([$firstAppliedDiscount, $secondAppliedDiscount])
        );
    }
}

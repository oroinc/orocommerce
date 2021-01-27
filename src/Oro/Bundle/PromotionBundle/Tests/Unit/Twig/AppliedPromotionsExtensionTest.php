<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Twig;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Repository\AppliedPromotionRepository;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Order;
use Oro\Bundle\PromotionBundle\Twig\AppliedPromotionsExtension;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Twig\TwigFunction;

class AppliedPromotionsExtensionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;
    use TwigExtensionTestCaseTrait;

    const CURRENCY_CODE = 'USD';
    const DISCOUNT_TYPE = 'line_item';
    const FIRST_NAME = 'First promotion name';
    const SECOND_NAME = 'Second promotion name';
    const COUPON_CODE = 'code123';
    const PROMOTION_ID = 37;
    const AMOUNT = 68.90;
    const CURRENCY = 'USD';
    const TYPE = 'order';

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var AppliedPromotionsExtension
     */
    private $appliedDiscountsExtension;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $container = self::getContainerBuilder()
            ->add('doctrine', $this->registry)
            ->getContainer($this);

        $this->appliedDiscountsExtension = new AppliedPromotionsExtension($container);
    }

    public function testGetFunctions()
    {
        $extensionFunctions = $this->appliedDiscountsExtension->getFunctions();

        static::assertCount(2, $extensionFunctions);
        static::assertInstanceOf(TwigFunction::class, $extensionFunctions[0]);
        static::assertInstanceOf(TwigFunction::class, $extensionFunctions[1]);
    }

    public function testPrepareAppliedPromotionsInfo()
    {
        $firstAppliedDiscount = $this->getEntity(AppliedDiscount::class, [
            'currency' => self::CURRENCY_CODE,
            'amount' => 17.0,
        ]);
        $firstAppliedPromotion = $this->getEntity(AppliedPromotion::class, [
            'promotionName' => self::FIRST_NAME,
            'id' => 5,
            'source_promotion_id' => 5,
            'appliedDiscounts' => [$firstAppliedDiscount],
            'type' => self::DISCOUNT_TYPE,
        ]);

        $secondAppliedDiscount = $this->getEntity(AppliedDiscount::class, [
            'currency' => self::CURRENCY_CODE,
            'amount' => 35.0,
        ]);

        $appliedCoupon = $this->getEntity(AppliedCoupon::class, [
            'couponCode' => 'summer',
            'sourceCouponId' => 7
        ]);
        $secondAppliedPromotion = $this->getEntity(AppliedPromotion::class, [
            'promotionName' => self::SECOND_NAME,
            'id' => 6,
            'source_promotion_id' => 6,
            'appliedDiscounts' => [$secondAppliedDiscount],
            'type' => self::DISCOUNT_TYPE,
            'appliedCoupon' => $appliedCoupon
        ]);

        $expectedItems = [
            [
                'id' => 5,
                'couponCode' => null,
                'promotionName' => self::FIRST_NAME,
                'active' => true,
                'amount' => 17.0,
                'currency' => self::CURRENCY_CODE,
                'type' => self::DISCOUNT_TYPE,
                'sourcePromotionId' => 5,
                'sourceCouponId' => null,
            ],
            [
                'id' => 6,
                'couponCode' => 'summer',
                'promotionName' => self::SECOND_NAME,
                'active' => true,
                'amount' => 35.0,
                'currency' => self::CURRENCY_CODE,
                'type' => self::DISCOUNT_TYPE,
                'sourcePromotionId' => 6,
                'sourceCouponId' => 7
            ]
        ];

        $this->assertEquals($expectedItems, $this->appliedDiscountsExtension->prepareAppliedPromotionsInfo(
            new ArrayCollection([$firstAppliedPromotion, $secondAppliedPromotion])
        ));
    }

    public function testPrepareAppliedPromotionsInfoWithGrouping()
    {
        $firstAppliedDiscount = $this->getEntity(AppliedDiscount::class, [
            'currency' => self::CURRENCY_CODE,
            'amount' => 17.0,
        ]);
        $secondAppliedDiscount = $this->getEntity(AppliedDiscount::class, [
            'currency' => self::CURRENCY_CODE,
            'amount' => 35.0,
        ]);
        $appliedPromotion = $this->getEntity(AppliedPromotion::class, [
            'promotionName' => self::FIRST_NAME,
            'id' => 5,
            'source_promotion_id' => 5,
            'appliedDiscounts' => [$firstAppliedDiscount, $secondAppliedDiscount],
            'type' => self::DISCOUNT_TYPE,
        ]);

        $expectedItems = [
            [
                'id' => 5,
                'couponCode' => null,
                'promotionName' => self::FIRST_NAME,
                'active' => true,
                'amount' => 52.0,
                'currency' => self::CURRENCY_CODE,
                'type' => self::DISCOUNT_TYPE,
                'sourcePromotionId' => 5,
                'sourceCouponId' => null,
            ],
        ];

        $this->assertEquals($expectedItems, $this->appliedDiscountsExtension->prepareAppliedPromotionsInfo(
            new ArrayCollection([$appliedPromotion])
        ));
    }

    public function testPrepareAppliedPromotionsOrdering()
    {
        $firstAppliedDiscount = $this->getEntity(AppliedDiscount::class, [
            'currency' => self::CURRENCY_CODE,
            'amount' => 17.0,
        ]);

        $firstAppliedPromotion = $this->getEntity(AppliedPromotion::class, [
            'promotionName' => self::FIRST_NAME,
            'id' => 5,
            'source_promotion_id' => 5,
            'appliedDiscounts' => [$firstAppliedDiscount],
            'type' => self::DISCOUNT_TYPE,
        ]);

        $secondAppliedDiscount = $this->getEntity(AppliedDiscount::class, [
            'currency' => self::CURRENCY_CODE,
            'amount' => 35.0,
        ]);

        $secondAppliedPromotion = $this->getEntity(AppliedPromotion::class, [
            'promotionName' => self::SECOND_NAME,
            'id' => 3,
            'source_promotion_id' => 6,
            'appliedDiscounts' => [$secondAppliedDiscount],
            'type' => self::DISCOUNT_TYPE
        ]);

        $newAppliedDiscount = $this->getEntity(AppliedDiscount::class, [
            'currency' => self::CURRENCY_CODE,
            'amount' => 111.0,
        ]);

        $newAppliedPromotion = $this->getEntity(AppliedPromotion::class, [
            'promotionName' => 'Newly created applied promotion',
            'source_promotion_id' => 10,
            'appliedDiscounts' => [$newAppliedDiscount],
            'type' => self::DISCOUNT_TYPE
        ]);

        $expectedItems = [
            [
                'id' => 3,
                'couponCode' => null,
                'promotionName' => self::SECOND_NAME,
                'active' => true,
                'amount' => 35.0,
                'currency' => self::CURRENCY_CODE,
                'type' => self::DISCOUNT_TYPE,
                'sourcePromotionId' => 6,
                'sourceCouponId' => null
            ],
            [
                'id' => 5,
                'couponCode' => null,
                'promotionName' => self::FIRST_NAME,
                'active' => true,
                'amount' => 17.0,
                'currency' => self::CURRENCY_CODE,
                'type' => self::DISCOUNT_TYPE,
                'sourcePromotionId' => 5,
                'sourceCouponId' => null,
            ],
            [
                'id' => null,
                'couponCode' => null,
                'promotionName' => 'Newly created applied promotion',
                'active' => true,
                'amount' => 111.0,
                'currency' => self::CURRENCY_CODE,
                'type' => self::DISCOUNT_TYPE,
                'sourcePromotionId' => 10,
                'sourceCouponId' => null,
            ],
        ];

        $this->assertEquals($expectedItems, $this->appliedDiscountsExtension->prepareAppliedPromotionsInfo(
            new ArrayCollection([$newAppliedPromotion, $firstAppliedPromotion, $secondAppliedPromotion])
        ));
    }

    public function testGetAppliedPromotionsInfo()
    {
        $order = new Order();

        $info = [
            [
                'couponCode' => self::COUPON_CODE,
                'promotionName' => self::FIRST_NAME,
                'promotionId' => self::PROMOTION_ID,
                'active' => false,
                'amount' => self::AMOUNT,
                'currency' => self::CURRENCY,
                'type' => self::TYPE
            ]
        ];

        $entityRepository = $this->createMock(AppliedPromotionRepository::class);
        $entityRepository
            ->expects($this->once())
            ->method('getAppliedPromotionsInfo')
            ->with($order)
            ->willReturn($info);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(AppliedPromotion::class)
            ->willReturn($entityRepository);

        $this->registry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(AppliedPromotion::class)
            ->willReturn($entityManager);

        $this->assertEquals($info, $this->appliedDiscountsExtension->getAppliedPromotionsInfo($order));
    }
}

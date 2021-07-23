<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Twig;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\CouponGeneration\Code\CodeGenerator;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CodeGenerationOptions;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Repository\AppliedPromotionRepository;
use Oro\Bundle\PromotionBundle\Layout\DataProvider\DiscountsInformationDataProvider;
use Oro\Bundle\PromotionBundle\Layout\DataProvider\DTO\ObjectStorage;
use Oro\Bundle\PromotionBundle\Twig\PromotionExtension;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class PromotionExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;
    use EntityTrait;

    private const CURRENCY_CODE = 'USD';
    private const DISCOUNT_TYPE = 'line_item';
    private const FIRST_NAME = 'First promotion name';
    private const SECOND_NAME = 'Second promotion name';
    private const COUPON_CODE = 'code123';
    private const PROMOTION_ID = 37;
    private const AMOUNT = 68.90;
    private const CURRENCY = 'USD';
    private const TYPE = 'order';

    /** @var DiscountsInformationDataProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $discountsInformationDataProvider;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var CodeGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $couponCodeGenerator;

    /** @var PromotionExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->discountsInformationDataProvider = $this->createMock(DiscountsInformationDataProvider::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->couponCodeGenerator = $this->createMock(CodeGenerator::class);

        $container = self::getContainerBuilder()
            ->add('oro_promotion.layout.discount_information_data_provider', $this->discountsInformationDataProvider)
            ->add(ManagerRegistry::class, $this->doctrine)
            ->add('oro_promotion.coupon_generation.code_generator', $this->couponCodeGenerator)
            ->getContainer($this);

        $this->extension = new PromotionExtension($container);
    }

    public function testGetEmptyLineItemsDiscounts()
    {
        $lineItem = $this->getEntity(OrderLineItem::class, ['id' => 1]);
        $sourceEntity = $this->getEntity(
            Order::class,
            [
                'id' => 1,
                'lineItems' => [$lineItem]
            ]
        );

        $this->discountsInformationDataProvider->expects($this->once())
            ->method('getDiscountLineItemDiscounts')
            ->with($sourceEntity)
            ->willReturn(new ObjectStorage());

        $this->assertEquals(
            [1 => null],
            self::callTwigFunction($this->extension, 'line_items_discounts', [$sourceEntity])
        );
    }

    public function testGetLineItemsDiscounts()
    {
        $lineItem1Id = 1;
        $lineItem2Id = 2;
        $lineItem1 = $this->getEntity(OrderLineItem::class, ['id' => $lineItem1Id]);
        $lineItem2 = $this->getEntity(OrderLineItem::class, ['id' => $lineItem2Id]);
        $sourceEntity = $this->getEntity(
            Order::class,
            [
                'id' => 2,
                'lineItems' => [$lineItem1, $lineItem2]
            ]
        );

        $priceData = ['value' => 3, 'currency' => 'USD'];
        $price = $this->getEntity(Price::class, $priceData);
        $lineItemsDiscounts = new ObjectStorage();
        $lineItemsDiscounts->attach(
            $lineItem1,
            [
                'total' => $price
            ]
        );

        $this->discountsInformationDataProvider->expects($this->once())
            ->method('getDiscountLineItemDiscounts')
            ->with($sourceEntity)
            ->willReturn($lineItemsDiscounts);

        $expectedDiscounts = [
            $lineItem1Id => $priceData,
            $lineItem2Id => null
        ];
        $this->assertEquals(
            $expectedDiscounts,
            self::callTwigFunction($this->extension, 'line_items_discounts', [$sourceEntity])
        );
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

        $this->assertEquals(
            $expectedItems,
            self::callTwigFunction(
                $this->extension,
                'oro_promotion_prepare_applied_promotions_info',
                [new ArrayCollection([$firstAppliedPromotion, $secondAppliedPromotion])]
            )
        );
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

        $this->assertEquals(
            $expectedItems,
            self::callTwigFunction(
                $this->extension,
                'oro_promotion_prepare_applied_promotions_info',
                [new ArrayCollection([$appliedPromotion])]
            )
        );
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

        $this->assertEquals(
            $expectedItems,
            self::callTwigFunction(
                $this->extension,
                'oro_promotion_prepare_applied_promotions_info',
                [new ArrayCollection([$newAppliedPromotion, $firstAppliedPromotion, $secondAppliedPromotion])]
            )
        );
    }

    public function testGetAppliedPromotionsInfo()
    {
        $order = new \Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Order();

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
        $entityRepository->expects($this->once())
            ->method('getAppliedPromotionsInfo')
            ->with($order)
            ->willReturn($info);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(AppliedPromotion::class)
            ->willReturn($entityRepository);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(AppliedPromotion::class)
            ->willReturn($entityManager);

        $this->assertEquals(
            $info,
            self::callTwigFunction($this->extension, 'oro_promotion_get_applied_promotions_info', [$order])
        );
    }

    public function testGenerateCouponCode()
    {
        $options = new CodeGenerationOptions();
        $generatedCode = 'coupon-code';

        $this->couponCodeGenerator->expects($this->once())
            ->method('generateOne')
            ->with($options)
            ->willReturn($generatedCode);

        $this->assertEquals(
            $generatedCode,
            self::callTwigFunction($this->extension, 'oro_promotion_generate_coupon_code', [$options])
        );
    }
}

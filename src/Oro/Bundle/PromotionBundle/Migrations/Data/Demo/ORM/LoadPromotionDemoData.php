<?php
declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM\LoadCategoryBasedSegmentsDemoData;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FixedProductShippingBundle\Integration\FixedProductChannelType;
use Oro\Bundle\FixedProductShippingBundle\Method\FixedProductMethodType;
use Oro\Bundle\FixedProductShippingBundle\Migrations\Data\Demo\ORM\LoadFixedProductIntegration;
use Oro\Bundle\FlatRateShippingBundle\Integration\FlatRateChannelType;
use Oro\Bundle\FlatRateShippingBundle\Method\FlatRateMethodType;
use Oro\Bundle\FlatRateShippingBundle\Migrations\Data\Demo\ORM\LoadFlatRateIntegration;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\BuyXGetYDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionSchedule;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Creates demo promotions.
 */
class LoadPromotionDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;
    use UserUtilityTrait;

    public const SEGMENT_NAME_FORMAT = 'Items to Discount in promotion "%s"';
    public const COUPON_CODE_SALE25 = 'SALE25';

    public function getDependencies(): array
    {
        return [
            LoadCategoryBasedSegmentsDemoData::class,
            LoadFixedProductIntegration::class,
            LoadFlatRateIntegration::class
        ];
    }

    /** @SuppressWarnings(PHPMD.ExcessiveMethodLength) */
    public function load(ObjectManager $manager)
    {
        $user = $this->getFirstUser($manager);
        $everyone = $this->container->get('oro_scope.scope_manager')
            ->findOrCreate('promotion', ['website' => null, 'customerGroup' => null, 'customer' => null]);
        $sortOrder = 1;


        $offTotalName = 'Seasonal Sale | 25% Off the order total with a coupon code';
        $offTotalPromo = (new Promotion())
            ->setOrganization($user->getOrganization())
            ->setOwner($user)
            ->setUseCoupons(true)
            ->setRule(
                (new Rule())
                    ->setName($offTotalName)
                    ->setSortOrder($sortOrder++)
                    ->setEnabled(true)
                    ->setStopProcessing(false)
            )
            ->addSchedule(
                (new PromotionSchedule())
                    ->setActiveAt((new \DateTime('now'))->sub(new \DateInterval('P1M')))
                    ->setDeactivateAt((new \DateTime('now'))->add(new \DateInterval('P6M')))
            )
            ->addScope($everyone)
            ->setDiscountConfiguration(
                (new DiscountConfiguration())
                    ->setType('order')
                    ->setOptions([
                        AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_PERCENT,
                        AbstractDiscount::DISCOUNT_VALUE => 0.25,
                    ])
            )
            ->setProductsSegment(
                $this->createProductSegment(
                    \sprintf(static::SEGMENT_NAME_FORMAT, $offTotalName),
                    $user,
                    $manager
                )
            )
            ->setDefaultLabel($offTotalName)
        ;
        $manager->persist($offTotalPromo);

        $coupon = (new Coupon())
            ->setOrganization($user->getOrganization())
            ->setOwner($user->getOwner())
            ->setCode(static::COUPON_CODE_SALE25)
            ->setEnabled(true)
            ->setUsesPerCoupon(100000)
            ->setUsesPerPerson(100000)
            ->setPromotion($offTotalPromo)
        ;
        $manager->persist($coupon);


        $buyXgetYName = 'Buy 10 Get 5 with $2 off on new arrivals in Medical Footwear';
        $buyXgetYPromo = (new Promotion())
            ->setOrganization($user->getOrganization())
            ->setOwner($user)
            ->setUseCoupons(false)
            ->setRule(
                (new Rule())
                    ->setName($buyXgetYName)
                    ->setSortOrder($sortOrder++)
                    ->setEnabled(true)
                    ->setStopProcessing(false)
            )
            ->addScope($everyone)
            ->setDiscountConfiguration(
                (new DiscountConfiguration())
                    ->setType('buy_x_get_y')
                    ->setOptions([
                        AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_AMOUNT,
                        AbstractDiscount::DISCOUNT_VALUE => 2,
                        AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                        BuyXGetYDiscount::BUY_X => 10,
                        BuyXGetYDiscount::GET_Y => 5,
                        BuyXGetYDiscount::DISCOUNT_PRODUCT_UNIT_CODE => 'set',
                        BuyXGetYDiscount::DISCOUNT_APPLY_TO => BuyXGetYDiscount::APPLY_TO_EACH_Y,
                        BuyXGetYDiscount::DISCOUNT_LIMIT => null,
                    ])
            )
            ->setProductsSegment(
                (
                    clone $manager->getRepository(Segment::class)
                    ->findOneByName(LoadCategoryBasedSegmentsDemoData::NEW_ARRIVALS_PREFIX . 'Footwear')
                )->setName(\sprintf(static::SEGMENT_NAME_FORMAT, $buyXgetYName))
            )
            ->setDefaultLabel($buyXgetYName)
        ;
        $manager->persist($buyXgetYPromo);


        /** @var ChannelRepository $intChannelRepo */
        $integrationChannelRepo = $manager->getRepository(Channel::class);
        $fixedProductShippingIntegrationChannel = $integrationChannelRepo->findOneBy([
            'type' => FixedProductChannelType::TYPE,
            'enabled' => true,
            'organization' => $user->getOrganization()
        ]);
        $generator = $this->container->get('oro_fixed_product_shipping.method.identifier_generator.method');
        $fixedProductMethodIdentifier = $generator->generateIdentifier($fixedProductShippingIntegrationChannel);

        $freeShippingLabel = 'Free shipping on orders $999 or more';
        $freeFixedShippingName = $freeShippingLabel . ' (fixed product shipping products)';
        $freeFixedShippingPromo = (new Promotion())
            ->setOrganization($user->getOrganization())
            ->setOwner($user)
            ->setUseCoupons(false)
            ->setRule(
                (new Rule())
                    ->setName($freeFixedShippingName)
                    ->setSortOrder($sortOrder++)
                    ->setEnabled(true)
                    ->setStopProcessing(false)
                    ->setExpression('subtotal > 999.0')
            )
            ->addScope($everyone)
            ->setDiscountConfiguration(
                (new DiscountConfiguration())
                    ->setType(ShippingDiscount::NAME)
                    ->setOptions([
                        AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_PERCENT,
                        AbstractDiscount::DISCOUNT_VALUE => 1,
                        ShippingDiscount::SHIPPING_OPTIONS => [
                            ShippingDiscount::SHIPPING_METHOD => $fixedProductMethodIdentifier,
                            ShippingDiscount::SHIPPING_METHOD_TYPE => FixedProductMethodType::IDENTIFIER,
                        ]
                    ])
            )
            ->setProductsSegment(
                $this->createProductSegment(
                    \sprintf(static::SEGMENT_NAME_FORMAT, $freeFixedShippingName),
                    $user,
                    $manager,
                    LoadFixedProductIntegration::PRODUCT_ID_THRESHOLD,
                    NumberFilterType::TYPE_LESS_EQUAL
                )
            )
            ->setDefaultLabel($freeShippingLabel)
        ;
        $manager->persist($freeFixedShippingPromo);


        /** @var ChannelRepository $intChannelRepo */
        $integrationChannelRepo = $manager->getRepository(Channel::class);
        $fixedProductShippingIntegrationChannel = $integrationChannelRepo->findOneBy([
            'type' => FlatRateChannelType::TYPE,
            'enabled' => true,
            'organization' => $user->getOrganization()
        ]);
        $generator = $this->container->get('oro_flat_rate_shipping.method.identifier_generator.method');
        $fixedProductMethodIdentifier = $generator->generateIdentifier($fixedProductShippingIntegrationChannel);

        $freeFlatShippingName = $freeShippingLabel . ' (flat rate shipping products)';
        $freeFlatShippingPromo = (new Promotion())
            ->setOrganization($user->getOrganization())
            ->setOwner($user)
            ->setUseCoupons(false)
            ->setRule(
                (new Rule())
                    ->setName($freeFlatShippingName)
                    ->setSortOrder($sortOrder++)
                    ->setEnabled(true)
                    ->setStopProcessing(false)
                    ->setExpression('subtotal > 999.0')
            )
            ->addScope($everyone)
            ->setDiscountConfiguration(
                (new DiscountConfiguration())
                    ->setType(ShippingDiscount::NAME)
                    ->setOptions([
                        AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_AMOUNT,
                        AbstractDiscount::DISCOUNT_VALUE => 10,
                        AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                        ShippingDiscount::SHIPPING_OPTIONS => [
                            ShippingDiscount::SHIPPING_METHOD => $fixedProductMethodIdentifier,
                            ShippingDiscount::SHIPPING_METHOD_TYPE => FlatRateMethodType::IDENTIFIER,
                        ]
                    ])
            )
            ->setProductsSegment(
                $this->createProductSegment(
                    \sprintf(static::SEGMENT_NAME_FORMAT, $freeFlatShippingName),
                    $user,
                    $manager,
                    LoadFixedProductIntegration::PRODUCT_ID_THRESHOLD,
                    NumberFilterType::TYPE_GREATER_THAN
                )
            )
            ->setDefaultLabel($freeShippingLabel)
        ;
        $manager->persist($freeFlatShippingPromo);

        $manager->flush();
    }

    protected function createProductSegment(
        string $name,
        User $user,
        ObjectManager $manager,
        ?int $productIdThreshold = null,
        ?int $productIdComparisonType = null
    ): Segment {
        $definition = [
            'columns' => [
                ['name' => 'id', 'label' => 'ID', 'sorting' => null, 'func' => null],
                ['name' => 'sku', 'label' => 'SKU', 'sorting' => null, 'func' => null],
            ],
            'filters' => [[
                [
                    'columnName' => 'status',
                    'criterion' => [
                        'filter' => 'string',
                        'data' => ['value' => Product::STATUS_ENABLED, 'type' => TextFilterType::TYPE_EQUAL]
                    ]
                ]
            ]]
        ];

        if (null !== $productIdThreshold && null !== $productIdComparisonType) {
            $definition['filters'][0][] = FilterUtility::CONDITION_AND;
            $definition['filters'][0][] = [
                'columnName' => 'id',
                'criterion' => [
                    'filter' => 'number',
                    'data' => ['value' => $productIdThreshold, 'type' => $productIdComparisonType]
                ]
            ];
        }

        $segment = (new Segment())
            ->setOrganization($user->getOrganization())
            ->setOwner($user->getOwner())
            ->setType($manager->getRepository(SegmentType::class)->findOneBy(['name' => SegmentType::TYPE_DYNAMIC]))
            ->setEntity(Product::class)
            ->setName($name)
            ->setDefinition(\json_encode($definition))
        ;

        /** @noinspection PhpUnreachableStatementInspection */
        $manager->persist($segment);

        return $segment;
    }
}

<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerAddresses;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserAddresses;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionSchedule;
use Oro\Bundle\PromotionBundle\EventListener\OrderSubtotalWithDiscountsListener;
use Oro\Bundle\PromotionBundle\Provider\EntityCouponsProviderInterface;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class OrderSubtotalWithDiscountsListenerTest extends WebTestCase
{
    private ManagerRegistry $doctrine;
    private EntityCouponsProviderInterface $entityCouponsProvider;
    private TotalHelper $totalHelper;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->doctrine = self::getContainer()->get('doctrine');
        $this->entityCouponsProvider = self::getContainer()->get('oro_promotion.provider.entity_coupons_provider');
        $this->totalHelper = self::getContainer()->get('oro_order.order.total.total_helper');

        $this->loadFixtures([
            LoadCustomers::class,
            LoadCustomerAddresses::class,
            LoadCustomerUserData::class,
            LoadCustomerUserAddresses::class,
            LoadOrderUsers::class,
        ]);
    }

    public function testListenerServiceDefinition(): void
    {
        $listener = self::getContainer()->get('oro_promotion.event_listener.order_subtotal_with_discounts_listener');
        self::assertInstanceOf(OrderSubtotalWithDiscountsListener::class, $listener);
    }

    public function testGetSubtotalWithDiscounts(): void
    {
        $order = $this->createOrder();
        $order->setSubtotal(100);
        $order->setTotal(100);
        $em = $this->doctrine->getManager();
        $em->persist($order);
        $em->flush();
        self::assertEquals($order->getSubtotalWithDiscounts(), 100);
    }

    public function testGetSubtotalWithDiscountsAfterCreateOrderWithSpecialDiscounts(): void
    {
        $em = $this->doctrine->getManager();
        $order = $this->createOrder();
        $order->setSubtotal(100);
        $order->setTotal(100);
        $this->fillOrderDiscounts($order, [['percent' => 1.0, 'amount' => 1.0], ['percent' => 2.0, 'amount' => 2.0]]);

        $em->persist($order);
        $em->flush();

        self::assertEquals($order->getSubtotalWithDiscounts(), 97);
    }

    public function testGetSubtotalWithDiscountsAfterCreateOrderWithPromotion(): void
    {
        $em = $this->doctrine->getManager();
        $order = $this->createOrder();
        $product = $this->createProduct(['name' => 'Test Product', 'sku' => 'SKU1']);
        $lineItem = $this->createOrderLineItem($order, $product, Price::create(100.00, 'USD'));
        $order->addLineItem($lineItem);
        $this->fillOrderPromotion($order, $lineItem, 'Seasonal Sale 1', 'SALE1');
        $this->totalHelper->fill($order);
        $em->persist($order);
        $em->flush();

        self::assertEquals($order->getSubtotalWithDiscounts(), 90.00);
    }

    public function testGetSubtotalWithDiscountsAfterCreateOrderWithPromotionAndSpecialDiscount(): void
    {
        $em = $this->doctrine->getManager();
        $order = $this->createOrder();
        $this->fillOrderDiscounts($order, [['percent' => 1.0, 'amount' => 1.0], ['percent' => 2.0, 'amount' => 2.0]]);
        $product = $this->createProduct(['name' => 'Test Product', 'sku' => 'SKU1']);
        $lineItem = $this->createOrderLineItem($order, $product, Price::create(100.00, 'USD'));
        $order->addLineItem($lineItem);
        $this->fillOrderPromotion($order, $lineItem, 'Seasonal Sale 2', 'SALE2');
        $this->totalHelper->fill($order);
        $em->persist($order);
        $em->flush();

        self::assertEquals($order->getSubtotalWithDiscounts(), 87.00);
    }

    public function testGetSubtotalWithDiscountsAfterCreateOrderWithDisabledPromotionAndSpecialDiscount(): void
    {
        $em = $this->doctrine->getManager();
        $order = $this->createOrder();
        $this->fillOrderDiscounts($order, [['percent' => 1.0, 'amount' => 1.0], ['percent' => 2.0, 'amount' => 2.0]]);
        $product = $this->createProduct(['name' => 'Test Product', 'sku' => 'SKU1']);
        $lineItem = $this->createOrderLineItem($order, $product, Price::create(100.00, 'USD'));
        $order->addLineItem($lineItem);
        $this->fillOrderPromotion($order, $lineItem, 'Seasonal Sale 3', 'SALE3');
        $this->totalHelper->fill($order);
        $order->setDisablePromotions(true);
        $em->persist($order);
        $em->flush();

        self::assertEquals($order->getSubtotalWithDiscounts(), 97.00);
    }

    private function createOrder(): Order
    {
        /** @var User $user */
        $user = $this->getReference(LoadOrderUsers::ORDER_USER_1);
        if (!$user->getOrganization()) {
            $user->setOrganization($this->doctrine->getRepository(Organization::class)->findOneBy([]));
        }
        /** @var CustomerUser $customerUser */
        $customerUser = $this->doctrine->getRepository(CustomerUser::class)->findOneBy([]);

        return (new Order())
            ->setOwner($user)
            ->setOrganization($user->getOrganization())
            ->setShipUntil(new \DateTime())
            ->setCurrency('USD')
            ->setCustomer($customerUser->getCustomer())
            ->setWebsite($this->getDefaultWebsite())
            ->setCustomerUser($customerUser);
    }

    private function fillOrderDiscounts(Order $order, array $discounts): void
    {
        foreach ($discounts as $discount) {
            $orderDiscount = new OrderDiscount();
            $orderDiscount
                ->setOrder($order)
                ->setPercent($discount['percent'])
                ->setAmount($discount['amount']);
            $order->addDiscount($orderDiscount);
        }
    }

    private function fillOrderPromotion(
        Order $order,
        OrderLineItem $lineItem,
        string $offTotalName,
        string $couponCode
    ): void {
        $manager = $this->doctrine->getManager();

        $userOwner = $manager->getRepository(BusinessUnit::class)->findOneBy([]);
        $user = $this->getReference(LoadOrderUsers::ORDER_USER_1);
        $user->setOwner($userOwner);

        $everyone = self::getContainer()
            ->get('oro_scope.scope_manager')
            ->findOrCreate('promotion', ['website' => null, 'customerGroup' => null, 'customer' => null]);

        $offTotalPromo = $this->createPromotion($user, $everyone, $offTotalName, 1);
        $manager->persist($offTotalPromo);

        $coupon = $this->createCoupon($user, $offTotalPromo, $couponCode);

        $manager->persist($coupon);
        $manager->flush();

        $appliedCoupon = $this->entityCouponsProvider->createAppliedCouponByCoupon($coupon);
        $manager->persist($appliedCoupon);

        $order->addAppliedCoupon($appliedCoupon);

        $appliedDiscount = $this->createAppliedDiscount($lineItem, 'USD', 10.00);
        $manager->persist($appliedCoupon);

        $appliedPromo = $this->createAppliedPromotion(
            $offTotalPromo,
            $appliedCoupon,
            $appliedDiscount,
            $everyone,
            $offTotalName,
            $offTotalName
        );
        $manager->persist($appliedPromo);

        $order->addAppliedPromotion($appliedPromo);
    }

    private function getDefaultWebsite(): Website
    {
        return $this->doctrine->getRepository(Website::class)->findOneBy(['default' => true]);
    }

    protected function createProductSegment(
        string $name,
        User $user,
        ObjectManager $manager
    ): Segment {
        $definition = [
            'columns' => [
                ['name' => 'id', 'label' => 'ID', 'sorting' => null, 'func' => null],
                ['name' => 'sku', 'label' => 'SKU', 'sorting' => null, 'func' => null],
            ],
            'filters' => [
                [
                    [
                        'columnName' => 'status',
                        'criterion' => [
                            'filter' => 'string',
                            'data' => ['value' => Product::STATUS_ENABLED, 'type' => TextFilterType::TYPE_EQUAL],
                        ],
                    ],
                ],
            ],
        ];

        return (new Segment())
            ->setOrganization($user->getOrganization())
            ->setOwner($user->getOwner())
            ->setType(
                $manager
                    ->getRepository(SegmentType::class)
                    ->findOneBy(['name' => SegmentType::TYPE_DYNAMIC])
            )
            ->setEntity(Product::class)
            ->setName($name)
            ->setDefinition(\json_encode($definition));
    }

    private function createProduct(array $productData): Product
    {
        $em = $this->doctrine->getManager();
        $productName = new ProductName();
        $productName->setString($productData['name']);
        $product = (new Product())
            ->setStatus(Product::STATUS_ENABLED)
            ->setType(Product::TYPE_SIMPLE)
            ->setSku($productData['sku'])
            ->addName($productName);
        $em->persist($product);
        $em->flush();
        return $product;
    }

    private function createOrderLineItem(Order $order, Product $product, Price $price): OrderLineItem
    {
        return (new OrderLineItem())
            ->setOrder($order)
            ->setProduct($product)
            ->setPrice($price)
            ->setQuantity(1.0);
    }

    private function createPromotion(User $owner, Scope $scope, string $name, int $sortOrder): Promotion
    {
        $manager = $this->doctrine->getManager();
        $promo = (new Promotion())
            ->setOrganization($owner->getOrganization())
            ->setOwner($owner)
            ->setUseCoupons(true)
            ->setRule(
                (new Rule())
                    ->setName($name)
                    ->setSortOrder($sortOrder)
                    ->setEnabled(true)
                    ->setStopProcessing(false)
            )
            ->addSchedule(
                (new PromotionSchedule())
                    ->setActiveAt((new \DateTime('now'))->sub(new \DateInterval('P1M')))
                    ->setDeactivateAt((new \DateTime('now'))->add(new \DateInterval('P6M')))
            )
            ->addScope($scope)
            ->setDiscountConfiguration(
                (new DiscountConfiguration())
                    ->setType('order')
                    ->setOptions([
                        AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_AMOUNT,
                        AbstractDiscount::DISCOUNT_VALUE => 10,
                    ])
            )
            ->setProductsSegment(
                $this->createProductSegment(
                    \sprintf('Items to Discount in promotion "%s"', $name),
                    $owner,
                    $manager
                )
            );
        $promo->setDefaultLabel($name);
        return $promo;
    }

    private function createCoupon(UserInterface $user, Promotion $promotion, string $code): Coupon
    {
        return (new Coupon())
            ->setOrganization($user->getOrganization())
            ->setOwner($user->getOwner())
            ->setCode($code)
            ->setEnabled(true)
            ->setUsesPerCoupon(100000)
            ->setUsesPerPerson(100000)
            ->setPromotion($promotion);
    }

    private function createAppliedPromotion(
        Promotion $promotion,
        AppliedCoupon $appliedCoupon,
        AppliedDiscount $appliedDiscount,
        Scope $scope,
        string $promotionName,
        string $ruleName
    ) {
        return (new AppliedPromotion())
            ->setSourcePromotionId($promotion->getId())
            ->setPromotionName($promotionName)
            ->setType('order')
            ->setActive(true)
            ->setAppliedCoupon($appliedCoupon)
            ->setConfigOptions([
                AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_AMOUNT,
                AbstractDiscount::DISCOUNT_VALUE => 10,
            ])
            ->addAppliedDiscount($appliedDiscount)
            ->setPromotionData([
                'id' => $promotion->getId(),
                'useCoupons' => true,
                'rule' => [
                    'name' => $ruleName,
                    'expression' => null,
                    'sortOrder' => 1,
                    'isStopProcessing' => false,
                ],
                'productsSegment' => [
                    'definition' => '{"columns":' .
                        '[{"name":"id","label":"ID","sorting":null,"func":null},' .
                        '{"name":"sku","label":"SKU","sorting":null,"func":null}],' .
                        '"filters":[[{"columnName":"status",' .
                        '"criterion":{"filter":"string","data":{"value":"enabled","type":3}}}]]}',
                ],
                'scopes' => [
                    0 => [
                        'id' => $scope->getId(),
                    ],
                ],
            ]);
    }

    private function createAppliedDiscount(OrderLineItem $lineItem, string $currency, float $amount): AppliedDiscount
    {
        return (new AppliedDiscount())
            ->setCurrency($currency)
            ->setAmount($amount)
            ->setLineItem($lineItem);
    }
}

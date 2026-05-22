<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterTypeInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountProductUnitCodeAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads order line item draft data with promotions for taxes and discounts calculation testing.
 */
class LoadOrderLineItemDraftDataWithTaxesAndDiscounts extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    public const string PROMOTION_LINE_ITEM_DISCOUNT = 'line_item_discount_promotion';
    public const string SEGMENT_LINE_ITEM_DISCOUNT = 'line_item_discount_segment';

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadOrderLineItemDraftDataWithTaxes::class,
            LoadUser::class,
            LoadOrganization::class,
            LoadBusinessUnit::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);

        // Create a segment that matches products used in the tests
        $segment = $this->createProductSegment($manager, $user);

        // Create a line item discount promotion (10% off)
        $promotion = $this->createLineItemDiscountPromotion($manager, $user, $segment);

        $manager->persist($segment);
        $manager->persist($promotion);
        $manager->flush();

        $this->setReference(self::PROMOTION_LINE_ITEM_DISCOUNT, $promotion);
        $this->setReference(self::SEGMENT_LINE_ITEM_DISCOUNT, $segment);
    }

    private function createProductSegment(ObjectManager $manager, User $user): Segment
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        $segment = new Segment();
        $segment->setName('Order Line Item Draft Discount Segment');
        $segment->setDescription('Products eligible for discount in order line item draft tests');
        $segment->setEntity(Product::class);
        $segment->setType(
            $manager->getRepository(SegmentType::class)->findOneBy(['name' => SegmentType::TYPE_DYNAMIC])
        );

        $organization = $user->getOrganization();
        $segment->setOrganization($organization);
        $segment->setOwner($organization->getBusinessUnits()->first());

        $segment->setDefinition(
            QueryDefinitionUtil::encodeDefinition(
                [
                    'columns' => [
                        [
                            'name' => 'id',
                            'label' => 'id',
                            'sorting' => '',
                            'func' => null,
                        ],
                    ],
                    'filters' => [
                        [
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => [
                                    'value' => implode(',', [$product1->getId(), $product2->getId()]),
                                    'type' => NumberFilterTypeInterface::TYPE_IN
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );

        return $segment;
    }

    private function createLineItemDiscountPromotion(
        ObjectManager $manager,
        User $user,
        Segment $segment
    ): Promotion {
        $rule = new Rule();
        $rule->setName('Order Line Item Draft 10% Discount');
        $rule->setEnabled(true);
        $rule->setSortOrder(1);
        $rule->setStopProcessing(false);

        $discountConfiguration = new DiscountConfiguration();
        $discountConfiguration->setType('line_item');
        $discountConfiguration->setOptions([
            AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_PERCENT,
            AbstractDiscount::DISCOUNT_VALUE => 0.1, // 10% discount
            DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'liter',
        ]);

        $promotion = new Promotion();
        $promotion->setRule($rule);
        $promotion->setProductsSegment($segment);
        $promotion->setDiscountConfiguration($discountConfiguration);
        $promotion->setUseCoupons(false);
        $promotion->setOwner($user);
        $promotion->setOrganization($user->getOrganization());

        /** @var Order $order1 */
        $order1 = $this->getReference(LoadOrders::ORDER_1);
        // Ensure promotion is created before the order so it can be applied to it.
        $promotion->setCreatedAt((clone $order1->getCreatedAt())->modify('-10 seconds'));
        $promotion->setUpdatedAt($promotion->getCreatedAt());

        return $promotion;
    }
}

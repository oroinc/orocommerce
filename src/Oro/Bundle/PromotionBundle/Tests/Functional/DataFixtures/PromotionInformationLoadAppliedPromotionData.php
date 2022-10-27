<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class PromotionInformationLoadAppliedPromotionData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    const APPLIED_PROMOTION_1 = 'applied_promotion_1';

    /**
     * @var array
     */
    protected static $appliedPromotions = [
        self::APPLIED_PROMOTION_1 => [
            'promotion' => LoadPromotionData::ORDER_PERCENT_PROMOTION
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadSegmentData::class,
            LoadOrders::class,
            LoadPromotionData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $scope = $this->container->get('oro_scope.scope_manager')->findDefaultScope();
        foreach (self::$appliedPromotions as $reference => $appliedPromotion) {
            /** @var Promotion $promotion */
            $promotion = $this->getReference(LoadPromotionData::ORDER_PERCENT_PROMOTION);

            $appliedPromotion = new AppliedPromotion();
            $appliedPromotion->setSourcePromotionId($promotion->getId());
            $appliedPromotion->setPromotionName($promotion->getRule()->getName());
            $appliedPromotion->setSourcePromotionId($promotion->getId());
            $appliedPromotion->setConfigOptions($promotion->getDiscountConfiguration()->getOptions());
            $appliedPromotion->setType($promotion->getDiscountConfiguration()->getType());
            /** @var Segment $segment */
            $segment = $this->getReference(LoadSegmentData::PRODUCT_DYNAMIC_SEGMENT);

            $data = [
                'id' => $promotion->getId(),
                'useCoupons' => true,
                'rule' => [
                    'name' => $promotion->getRule()->getName(),
                    'expression' => $promotion->getRule()->getExpression(),
                    'sortOrder' => $promotion->getRule()->getSortOrder(),
                    'isStopProcessing' => $promotion->getRule()->isStopProcessing()
                ],
                'productsSegment' => [
                    'definition' => $segment->getDefinition()
                ],
                'scopes' => [
                    ['id' => $scope->getId()]
                ]
            ];
            $appliedPromotion->setPromotionData($data);

            $manager->persist($appliedPromotion);

            $this->setReference($reference, $appliedPromotion);
        }

        $manager->flush();
    }
}

<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

class LoadPromotionData extends AbstractFixture implements DependentFixtureInterface
{
    const SIMPLE_PROMOTION = 'simple_promotion';

    /** @var array */
    protected static $promotions = [
        self::SIMPLE_PROMOTION => [
            'rule' => [
                'name' => 'Simple promotion name',
                'sortOrder' => 100,
                'enabled' => true,
            ],
            'segmentReference' => LoadSegmentData::PRODUCT_STATIC_SEGMENT,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadSegmentData::class,
        ];
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $userRepository = $manager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => LoadAdminUserData::DEFAULT_ADMIN_EMAIL]);

        foreach (static::$promotions as $reference => $promotionData) {
            $rule = new Rule();
            $rule->setName($promotionData['rule']['name']);
            $rule->setSortOrder($promotionData['rule']['sortOrder']);
            $rule->setEnabled($promotionData['rule']['enabled']);

            $promotion = new Promotion();
            $promotion->setOwner($user);
            $promotion->setRule($rule);

            /** @var Segment $segment */
            $segment = $this->getReference($promotionData['segmentReference']);
            $promotion->setProductsSegment($segment);

            // TODO: Please remove it after implementing first discount configuration in BB-10088
            $promotion->setDiscountConfiguration(
                (new DiscountConfiguration())->setType('someType')
            );
            $manager->persist($promotion);

            $this->setReference($reference, $promotion);
        }
        $manager->flush();
    }
}

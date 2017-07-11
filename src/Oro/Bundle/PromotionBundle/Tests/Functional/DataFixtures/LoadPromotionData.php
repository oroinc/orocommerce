<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadPromotionData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    const ORDER_PERCENT_PROMOTION = 'order_percent_promotion';
    const ORDER_AMOUNT_PROMOTION = 'order_amount_promotion';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /** @var array */
    protected static $promotions = [
        self::ORDER_PERCENT_PROMOTION => [
            'rule' => [
                'name' => 'Order percent promotion name',
                'sortOrder' => 100,
                'enabled' => true,
            ],
            'segmentReference' => LoadSegmentData::PRODUCT_DYNAMIC_SEGMENT,
            'discountConfiguration' => LoadDiscountConfigurationData::DISCOUNT_CONFIGURATION_ORDER_PERCENT,
            'scopeCriterias' => [
                [
                    'website' => null,
                    'customerGroup' => null,
                    'customer' => null
                ]
            ]
        ],
        self::ORDER_AMOUNT_PROMOTION => [
            'rule' => [
                'name' => 'Order percent promotion name',
                'sortOrder' => 200,
                'enabled' => true,
            ],
            'segmentReference' => LoadSegmentData::PRODUCT_DYNAMIC_SEGMENT,
            'discountConfiguration' => LoadDiscountConfigurationData::DISCOUNT_CONFIGURATION_ORDER_AMOUNT,
            'scopeCriterias' => [
                [
                    'website' => null,
                    'customerGroup' => null,
                    'customer' => null
                ]
            ]
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadSegmentData::class,
            LoadDiscountConfigurationData::class,
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

            /** @var DiscountConfiguration $discountConfiguration */
            $discountConfiguration = $this->getReference($promotionData['discountConfiguration']);

            /** @var Segment $segment */
            $segment = $this->getReference($promotionData['segmentReference']);

            $promotion->setDiscountConfiguration($discountConfiguration);
            $promotion->setProductsSegment($segment);

            foreach ($promotionData['scopeCriterias'] as $scopeCriteria) {
                $scopeCriteria = $this->getScope($scopeCriteria);
                $promotion->addScope($scopeCriteria);
            }

            $manager->persist($promotion);

            $this->setReference($reference, $promotion);
        }

        $manager->flush();
    }

    /**
     * @param array $scopeCriteria
     * @return Scope
     */
    private function getScope(array $scopeCriteria)
    {
        return $this->container->get('oro_scope.scope_manager')->findOrCreate('promotion', $scopeCriteria);
    }
}

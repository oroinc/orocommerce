<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

abstract class AbstractLoadPromotionData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $userRepository = $manager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => LoadAdminUserData::DEFAULT_ADMIN_EMAIL]);

        foreach ($this->getPromotions() as $reference => $promotionData) {
            $rule = new Rule();
            $rule->setName($promotionData['rule']['name']);
            $rule->setSortOrder($promotionData['rule']['sortOrder']);
            $rule->setEnabled($promotionData['rule']['enabled']);

            $promotion = new Promotion();
            $promotion->setOwner($user);
            $promotion->setOrganization($user->getOrganization());
            $promotion->setRule($rule);
            $promotion->setUseCoupons($promotionData['useCoupons']);

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
     * @return array
     */
    abstract protected function getPromotions();

    /**
     * @param array $scopeCriteria
     * @return Scope
     */
    private function getScope(array $scopeCriteria)
    {
        return $this->container->get('oro_scope.scope_manager')->findOrCreate('promotion', $scopeCriteria);
    }
}

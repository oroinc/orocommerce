<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

abstract class AbstractLoadPromotionData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadUser::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
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
            $promotion->setDiscountConfiguration($this->getReference($promotionData['discountConfiguration']));
            $promotion->setProductsSegment($this->getReference($promotionData['segmentReference']));
            foreach ($promotionData['scopeCriterias'] as $scopeCriteria) {
                $promotion->addScope($this->getScope($scopeCriteria));
            }

            $manager->persist($promotion);
            $this->setReference($reference, $promotion);
        }
        $manager->flush();
    }

    abstract protected function getPromotions(): array;

    private function getScope(array $scopeCriteria): Scope
    {
        return $this->container->get('oro_scope.scope_manager')->findOrCreate('promotion', $scopeCriteria);
    }
}

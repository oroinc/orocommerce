<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\ApiFrontend\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCompetedCheckoutData;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadSegmentData;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;

class LoadCheckoutPromotionalDiscountsData extends AbstractFixture implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadCheckoutData::class,
            LoadCompetedCheckoutData::class,
            LoadSegmentData::class,
            LoadUser::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);

        $rule = new Rule();
        $rule->setName('Line Item Promotion');
        $rule->setSortOrder(1);

        $promotion = new Promotion();
        $promotion->setOwner($user);
        $promotion->setOrganization($user->getOrganization());
        $promotion->setRule($rule);
        $promotion->setProductsSegment($this->getReference(LoadSegmentData::PRODUCT_DYNAMIC_SEGMENT));

        $discountConfiguration = new DiscountConfiguration();
        $discountConfiguration->setType('line_item');
        $discountConfiguration->setOptions([
            'discount_type' => 'amount',
            'discount_value' => 1,
            'discount_currency' => 'USD',
            'discount_product_unit_code' => 'milliliter',
            'apply_to' => 'line_items_total'
        ]);
        $promotion->setDiscountConfiguration($discountConfiguration);

        $manager->persist($promotion);
        $manager->flush();
    }
}

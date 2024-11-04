<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\UserBundle\Entity\User;

class TestEntityNameResolverDataLoader implements TestEntityNameResolverDataLoaderInterface
{
    private TestEntityNameResolverDataLoaderInterface $innerDataLoader;

    public function __construct(TestEntityNameResolverDataLoaderInterface $innerDataLoader)
    {
        $this->innerDataLoader = $innerDataLoader;
    }

    #[\Override]
    public function loadEntity(
        EntityManagerInterface $em,
        ReferenceRepository $repository,
        string $entityClass
    ): array {
        if (Coupon::class === $entityClass) {
            $coupon = new Coupon();
            $coupon->setOrganization($repository->getReference('organization'));
            $coupon->setOwner($repository->getReference('business_unit'));
            $coupon->setCode('Test Coupon');
            $repository->setReference('coupon', $coupon);
            $em->persist($coupon);
            $em->flush();

            return ['coupon'];
        }

        if (Promotion::class === $entityClass) {
            $rule = new Rule();
            $rule->setName('Test Promotion Rule');
            $rule->setSortOrder(1);
            $em->persist($rule);
            $discountConfiguration = new DiscountConfiguration();
            $discountConfiguration->setType('amount');
            $em->persist($discountConfiguration);
            $segment = new Segment();
            $segment->setOrganization($repository->getReference('organization'));
            $segment->setOwner($repository->getReference('business_unit'));
            $segment->setType($em->getRepository(SegmentType::class)->find(SegmentType::TYPE_DYNAMIC));
            $segment->setName('Test Segment');
            $segment->setEntity(User::class);
            $segment->setDefinition('{}');
            $em->persist($segment);
            $promotion = new Promotion();
            $promotion->setOrganization($repository->getReference('organization'));
            $promotion->setOwner($repository->getReference('user'));
            $promotion->setRule($rule);
            $promotion->setDiscountConfiguration($discountConfiguration);
            $promotion->setProductsSegment($segment);
            $repository->setReference('promotion', $promotion);
            $em->persist($promotion);
            $em->flush();

            return ['promotion'];
        }

        return $this->innerDataLoader->loadEntity($em, $repository, $entityClass);
    }

    #[\Override]
    public function getExpectedEntityName(
        ReferenceRepository $repository,
        string $entityClass,
        string $entityReference,
        ?string $format,
        ?string $locale
    ): string {
        if (Coupon::class === $entityClass) {
            return 'Test Coupon';
        }
        if (Promotion::class === $entityClass) {
            return 'Test Promotion Rule';
        }

        return $this->innerDataLoader->getExpectedEntityName(
            $repository,
            $entityClass,
            $entityReference,
            $format,
            $locale
        );
    }
}

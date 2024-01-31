<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadProductFamilyData extends AbstractFixture implements DependentFixtureInterface
{
    public const PRODUCT_FAMILY_1 = 'product_family_1';
    public const PRODUCT_FAMILY_2 = 'product_family_2';

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadOrganization::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $families = [self::PRODUCT_FAMILY_1, self::PRODUCT_FAMILY_2];
        foreach ($families as $familyName) {
            $family = new AttributeFamily();
            $family->setDefaultLabel($familyName);
            $family->setOwner($this->getReference(LoadOrganization::ORGANIZATION));
            $family->setCode($familyName);
            $family->setEntityClass(Product::class);
            $this->setReference($familyName, $family);
            $manager->persist($family);
        }
        $manager->flush();
    }
}

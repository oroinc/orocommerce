<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;

class LoadProductFamilyData extends AbstractFixture
{
    public const PRODUCT_FAMILY_1 = 'product_family_1';
    public const PRODUCT_FAMILY_2 = 'product_family_2';

    /** @var array */
    protected $families = [
        self::PRODUCT_FAMILY_1 => [],
        self::PRODUCT_FAMILY_2 => [],
    ];

    public function load(ObjectManager $manager)
    {
        foreach ($this->families as $familyName => $groups) {
            $family = new AttributeFamily();
            $family->setDefaultLabel($familyName);
            $family->setOwner($this->getOrganization($manager));
            $family->setCode($familyName);
            $family->setEntityClass(Product::class);

            $this->setReference($familyName, $family);
            $manager->persist($family);
        }

        $manager->flush();
    }

    private function getOrganization(ObjectManager $manager): Organization
    {
        return $manager->getRepository(Organization::class)->getFirst();
    }
}

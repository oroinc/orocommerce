<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

class LoadProductFamilyData extends AbstractFixture
{
    public const PRODUCT_FAMILY_1 = 'product_family_1';
    public const PRODUCT_FAMILY_2 = 'product_family_2';

    /** @var array */
    protected $families = [
        self::PRODUCT_FAMILY_1 => [],
        self::PRODUCT_FAMILY_2 => [],
    ];

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->families as $familyName => $groups) {
            $family = new AttributeFamily();
            $family->setDefaultLabel($familyName);
            $family->setOwner($this->getAdminUser($manager));
            $family->setCode($familyName);
            $family->setEntityClass(Product::class);

            $this->setReference($familyName, $family);
            $manager->persist($family);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @return User
     */
    private function getAdminUser(ObjectManager $manager): User
    {
        $repository = $manager->getRepository(User::class);

        return $repository->findOneBy(['username' => LoadAdminUserData::DEFAULT_ADMIN_USERNAME]);
    }
}

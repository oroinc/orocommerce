<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class LoadProductData extends AbstractFixture
{
    const TEST_PRODUCT_01 = 'test_product_01';
    const TEST_PRODUCT_02 = 'test_product_02';
    const TEST_PRODUCT_03 = 'test_product_03';

    /**
     * @var array
     */
    protected $products = [
        ['productCode' => self::TEST_PRODUCT_01],
        ['productCode' => self::TEST_PRODUCT_02],
        ['productCode' => self::TEST_PRODUCT_03]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $manager */
        $user = $this->getUser($manager);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();

        foreach ($this->products as $item) {
            $product = new Product();
            $product->setOwner($businessUnit)
                ->setOrganization($organization)
                ->setSku($item['productCode']);

            $manager->persist($product);
        }

        $manager->flush();
    }

    /**
     * @param EntityManager $manager
     * @return User
     * @throws \LogicException
     */
    protected function getUser(EntityManager $manager)
    {
        $user = $manager->getRepository('OroUserBundle:User')
            ->createQueryBuilder('user')
            ->orderBy('user.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();

        if (!$user) {
            throw new \LogicException('There are no users in system');
        }

        return $user;
    }
}

<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class LoadProductDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
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

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadAttributeDemoData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $manager */
        $user = $this->getUser($manager);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();

        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BProductBundle/Migrations/Data/Demo/ORM/data/products.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        $inventoryStatuses = $this->getAllEnumValuesByCode($manager, 'prod_inventory_status');
        $visibilities = $this->getAllEnumValuesByCode($manager, 'prod_visibility');
        $statuses = $this->getAllEnumValuesByCode($manager, 'prod_status');

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $name = new LocalizedFallbackValue();
            $name->setString($row['productName']);

            $description = new LocalizedFallbackValue();
            $description->setText(nl2br($row['productDescription']));

            $product = new Product();
            $product->setOwner($businessUnit)
                ->setOrganization($organization)
                ->setSku($row['productCode'])
                ->setInventoryStatus($inventoryStatuses[array_rand($inventoryStatuses)])
                ->setVisibility($visibilities[array_rand($visibilities)])
                ->setStatus($statuses[array_rand($statuses)])
                ->addName($name)
                ->addDescription($description);

            $manager->persist($product);
        }

        fclose($handler);

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

    /**
     * @param EntityManager $manager
     * @param string $title
     * @return Category|null
     */
    protected function getCategoryByDefaultTitle(EntityManager $manager, $title)
    {
        if (!array_key_exists($title, $this->categories)) {
            $this->categories[$title] =
                $manager->getRepository('OroB2BCatalogBundle:Category')->findOneByDefaultTitle($title);
        }

        return $this->categories[$title];
    }

    /**
     * @param ObjectManager $manager
     * @param string $enumCode
     * @return AbstractEnumValue[]
     */
    protected function getAllEnumValuesByCode(ObjectManager $manager, $enumCode)
    {
        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName($enumCode);

        return $manager->getRepository($inventoryStatusClassName)->findAll();
    }
}

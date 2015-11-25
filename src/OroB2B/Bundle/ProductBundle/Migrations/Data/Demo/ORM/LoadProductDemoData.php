<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class LoadProductDemoData extends AbstractFixture implements ContainerAwareInterface
{
    use UserUtilityTrait;

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
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $manager */
        $user = $this->getFirstUser($manager);
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

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $name = new LocalizedFallbackValue();
            $name->setString($row['name']);

            $description = new LocalizedFallbackValue();
            $description->setText(nl2br($row['description']));

            $product = new Product();
            $product->setOwner($businessUnit)
                ->setOrganization($organization)
                ->setSku($row['sku'])
                ->setInventoryStatus($inventoryStatuses[1])
                ->setStatus(Product::STATUS_ENABLED)
                ->addName($name)
                ->addDescription($description);

            $manager->persist($product);
        }

        fclose($handler);

        $manager->flush();
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

<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductImage;

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

        $allImageTypes = $this->getImageTypes();

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $name = new LocalizedFallbackValue();
            $name->setString($row['name']);

            $text = '<p>' . $row['description'] . '</p>'
                . (
                    array_key_exists('information', $row) && !empty($row['information']) ?
                    '<p style="text-decoration: underline; font-weight: bold;">Product Information &amp; Features:</p>'
                    . '<ul><li>' . implode('</li><li>', explode("\n", $row['information'])) . '</li></ul>'
                    : ''
                )
                . (
                    array_key_exists('specifications', $row) && !empty($row['specifications'])  ?
                    '<p style="text-decoration: underline; font-weight: bold;">Technical Specs:</p>'
                    . '<ul><li>' . implode('</li><li>', explode("\n", $row['specifications'])) . '</li></ul>'
                    : ''
                );

            $description = new LocalizedFallbackValue();
            $description->setText(nl2br($text));

            $shortDescription = new LocalizedFallbackValue();
            $shortDescription->setText($row['description']);

            $product = new Product();
            $product->setOwner($businessUnit)
                ->setOrganization($organization)
                ->setSku($row['sku'])
                ->setInventoryStatus($inventoryStatuses[1])
                ->setStatus(Product::STATUS_ENABLED)
                ->addName($name)
                ->addDescription($description)
                ->addShortDescription($shortDescription);

            $productImage = $this->getProductImageForProductSku($manager, $locator, $row['sku'], $allImageTypes);
            if ($productImage) {
                $product->addImage($productImage);
            }

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

    /**
     * @param ObjectManager $manager
     * @param FileLocator $locator
     * @param string $sku
     * @param array|null $types
     * @return null|ProductImage
     */
    protected function getProductImageForProductSku(ObjectManager $manager, FileLocator $locator, $sku, $types)
    {
        $productImage = null;

        try {
            $imagePath = $locator->locate(sprintf('@OroB2BProductBundle/Migrations/Data/Demo/ORM/images/%s.jpg', $sku));

            if (is_array($imagePath)) {
                $imagePath = current($imagePath);
            }

            $attachmentManager = $this->container->get('oro_attachment.manager');

            $image = $attachmentManager->prepareRemoteFile($imagePath);

            $attachmentManager->upload($image);

            $manager->persist($image);

            $productImage = new ProductImage();
            $productImage->setImage($image);
            foreach ($types as $type) {
                $productImage->addType($type);
            }
        } catch (\Exception $e) {
            //image not found
        }

        return $productImage;
    }

    /**
     * @return array
     */
    protected function getImageTypes()
    {
        $imageTypeProvider = $this->container->get('oro_layout.provider.image_type');

        return array_keys($imageTypeProvider->getImageTypes());
    }
}

<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Component\MessageQueue\Util\JSON;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadProductDemoData extends AbstractFixture implements ContainerAwareInterface
{
    use UserUtilityTrait;

    /**
     * @var string
     */
    const ENUM_CODE_INVENTORY_STATUS = 'prod_inventory_status';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $productUnis = [];

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
        $filePath = $locator->locate('@OroProductBundle/Migrations/Data/Demo/ORM/data/products.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'rb');
        $headers = fgetcsv($handler, 1000, ',');

        $outOfStockStatus = $this->getOutOfStockInventoryStatus($manager);

        $allImageTypes = $this->getImageTypes();
        $defaultAttributeFamily = $this->getDefaultAttributeFamily($manager);

        $slugGenerator = $this->container->get('oro_entity_config.slug.generator');
        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $name = new LocalizedFallbackValue();
            $name->setString($row['name']);

            $text = '<p  class="product-view-desc">' . $row['description'] . '</p>'
                . (
                    array_key_exists('information', $row) && !empty($row['information']) ?
                    '<p class="product-view-desc-title">Product Information &amp; Features:</p>
                    <ul class="product-view-desc-list"><li class="product-view-desc-list__item">'
                    .   implode('</li><li class="product-view-desc-list__item">', explode("\n", $row['information']))
                    . '</li></ul>'
                    : ''
                )
                . (
                    array_key_exists('specifications', $row) && !empty($row['specifications']) ?
                    '<p class="product-view-desc-title">Technical Specs:</p>'
                    . '<ul class="product-view-desc-list"><li class="product-view-desc-list__item">'
                    .   implode('</li><li class="product-view-desc-list__item">', explode("\n", $row['specifications']))
                    . '</li></ul>'
                    : ''
                );

            $description = new LocalizedFallbackValue();
            $description->setText(nl2br($text));

            $shortDescription = new LocalizedFallbackValue();
            $shortDescription->setText($row['description']);

            $product = new Product();
            $product->setOwner($businessUnit)
                ->setOrganization($organization)
                ->setAttributeFamily($defaultAttributeFamily)
                ->setSku($row['sku'])
                ->setInventoryStatus($outOfStockStatus)
                ->setStatus(Product::STATUS_ENABLED)
                ->addName($name)
                ->addDescription($description)
                ->addShortDescription($shortDescription)
                ->setType($row['type']);

            $product->setPageTemplate($this->getPageTemplate($row));

            $slugPrototype = new LocalizedFallbackValue();
            $slugPrototype->setString($slugGenerator->slugify($row['name']));
            $product->addSlugPrototype($slugPrototype);

            $productUnit = $this->getProductUnit($manager, $row['unit']);

            $productUnitPrecision = new ProductUnitPrecision();
            $productUnitPrecision
                ->setProduct($product)
                ->setUnit($productUnit)
                ->setPrecision((int)$row['precision'])
                ->setConversionRate(1)
                ->setSell(true);
            
            $product->setPrimaryUnitPrecision($productUnitPrecision);

            $productImage = $this->getProductImageForProductSku($manager, $locator, $row['sku'], $allImageTypes);
            if ($productImage) {
                $product->addImage($productImage);
            }

            $manager->persist($product);
        }

        fclose($handler);

        $manager->flush();

        $this->container->get('oro_message_queue.client.message_producer')->send(
            Topics::REGENERATE_DIRECT_URL_FOR_ENTITY_TYPE,
            JSON::encode(Product::class)
        );
    }

    /**
     * @param ObjectManager $manager
     * @return AbstractEnumValue|object
     *
     * @throws \InvalidArgumentException
     */
    protected function getOutOfStockInventoryStatus(ObjectManager $manager)
    {
        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName(self::ENUM_CODE_INVENTORY_STATUS);

        return $manager->getRepository($inventoryStatusClassName)->findOneBy([
            'id' => Product::INVENTORY_STATUS_OUT_OF_STOCK
        ]);
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
            $imagePath = $locator->locate(sprintf('@OroProductBundle/Migrations/Data/Demo/ORM/images/%s.jpg', $sku));

            if (is_array($imagePath)) {
                $imagePath = current($imagePath);
            }

            $fileManager = $this->container->get('oro_attachment.file_manager');
            $image = $fileManager->createFileEntity($imagePath);
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

    /**
     * @param EntityManager $manager
     * @param string $code
     * @return ProductUnit|null
     */
    protected function getProductUnit(EntityManager $manager, $code)
    {
        if (!array_key_exists($code, $this->productUnis)) {
            $this->productUnis[$code] = $manager->getRepository('OroProductBundle:ProductUnit')->find($code);
        }

        return $this->productUnis[$code];
    }

    /**
     * @param ObjectManager $manager
     * @return AttributeFamily|null
     */
    private function getDefaultAttributeFamily(ObjectManager $manager)
    {
        $familyRepository = $manager->getRepository(AttributeFamily::class);

        return $familyRepository->findOneBy(['code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE]);
    }

    /**
     * @param array $row
     * @return null|EntityFieldFallbackValue
     */
    private function getPageTemplate(array $row)
    {
        if (isset($row['page_template'])) {
            $entityFallbackValue = new EntityFieldFallbackValue();
            $fallbackValue = $entityFallbackValue
                ->setArrayValue([ProductType::PAGE_TEMPLATE_ROUTE_NAME => $row['page_template']]);
            return $fallbackValue;
        }

        return null;
    }
}

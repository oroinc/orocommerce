<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * @see \Oro\Bundle\EntityConfigBundle\Tests\Functional\Environment\TestEntitiesMigrationListener
 */
class LoadAttributeForConfigurableImportData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    const PRODUCT_ENTITY_CONFIG_MODEL = Product::class;
    const SKU_ATTRIBUTE = 'sku';
    const IS_FEATURED_ATTRIBUTE = 'featured';
    const NEW_ARRIVAL_ATTRIBUTE = 'newArrival';
    const NAME_ATTRIBUTE = 'denormalizedDefaultName';
    const INVENTORY_STATUS_ATTRIBUTE = 'inventory_status';

    /** @var array */
    private static $attributesData = [];

    #[\Override]
    public function load(ObjectManager $manager)
    {
        if (!empty(self::$attributesData)) {
            return;
        }

        $attributes = [
            self::SKU_ATTRIBUTE,
            self::IS_FEATURED_ATTRIBUTE,
            self::NEW_ARRIVAL_ATTRIBUTE,
            self::NAME_ATTRIBUTE,
            self::INVENTORY_STATUS_ATTRIBUTE,
        ];

        $configManager = $this->container->get('oro_entity_config.config_manager');
        foreach ($attributes as $attributeName) {
            self::$attributesData[$attributeName] = self::getAttribute($configManager, $attributeName)->getId();
        }
    }

    public static function getAttributeIdByName(string $attributeName): ?int
    {
        return self::$attributesData[$attributeName] ?? null;
    }

    public static function getAttribute(ConfigManager $configManager, string $attributeName): FieldConfigModel
    {
        $attribute = $configManager->getConfigFieldModel(self::PRODUCT_ENTITY_CONFIG_MODEL, $attributeName);
        if (null === $attribute) {
            throw new \RuntimeException(
                sprintf('The attribute "%s::%s" not found.', self::PRODUCT_ENTITY_CONFIG_MODEL, $attributeName)
            );
        }

        return $attribute;
    }
}

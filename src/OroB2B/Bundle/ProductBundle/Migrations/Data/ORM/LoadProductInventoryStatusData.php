<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class LoadProductInventoryStatusData extends AbstractEnumFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var ConfigProvider $configProvider */
        $configProvider = $this->container->get('oro_entity_config.provider.importexport');
        $configManager = $configProvider->getConfigManager();

        $enumClass = ExtendHelper::buildEnumValueClassName($this->getEnumCode());
        $id = $configProvider->getConfig($enumClass, 'id');
        $id->set('identity', true);
        $name = $configProvider->getConfig($enumClass, 'name');
        $name->remove('identity');

        $configManager->persist($id);
        $configManager->persist($name);
        $configManager->flush();

        parent::load($manager);
    }

    /**
     * {@inheritdoc}
     */
    protected function getData()
    {
        return [
            Product::INVENTORY_STATUS_IN_STOCK     => 'In Stock',
            Product::INVENTORY_STATUS_OUT_OF_STOCK => 'Out of Stock',
            Product::INVENTORY_STATUS_DISCONTINUED => 'Discontinued'
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getEnumCode()
    {
        return 'prod_inventory_status';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultValue()
    {
        return Product::INVENTORY_STATUS_IN_STOCK;
    }
}

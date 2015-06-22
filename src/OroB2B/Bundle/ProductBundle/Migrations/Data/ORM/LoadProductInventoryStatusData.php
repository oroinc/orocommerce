<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class LoadProductInventoryStatusData extends AbstractFixture
{
    /** @var array */
    protected $data = [
        'In Stock'     => Product::INVENTORY_STATUS_IN_STOCK,
        'Out of Stock' => Product::INVENTORY_STATUS_OUT_OF_STOCK,
        'Discontinued' => Product::INVENTORY_STATUS_DISCONTINUED,
    ];

    /** @var string */
    protected $defaultValue = 'In Stock';

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $className = ExtendHelper::buildEnumValueClassName('prod_inventory_status');

        /** @var EnumValueRepository $enumRepo */
        $enumRepo = $manager->getRepository($className);

        $priority = 1;
        foreach ($this->data as $name => $id) {
            $isDefault = $name === $this->defaultValue;
            $enumOption = $enumRepo->createEnumValue($name, $priority++, $isDefault, $id);
            $manager->persist($enumOption);
        }

        $manager->flush();
    }
}

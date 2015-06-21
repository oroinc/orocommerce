<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class LoadProductVisibilityData extends AbstractFixture
{
    /** @var array */
    protected $data = [
        'As Defined in System Configuration' => Product::VISIBILITY_BY_CONFIG,
        'Yes'                                => Product::VISIBILITY_VISIBLE,
        'No'                                 => Product::VISITBILITY_NO,
    ];

    /** @var string */
    protected $defaultValue = 'As Defined in System Configuration';

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $className = ExtendHelper::buildEnumValueClassName('prod_visibility');

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

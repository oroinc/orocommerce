<?php

namespace OroB2B\Bundle\AttributeBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeLabel;

abstract class AbstractLoadAttributeData extends AbstractFixture
{
    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->attributes as $item) {
            // Create attribute label
            $label = new AttributeLabel();
            $label->setValue($item['label']);

            // Create attribute
            $attribute = new Attribute();
            $attribute->setCode($item['code']);
            $attribute->setType($item['type']);
            $attribute->setSharingType('global');
            $attribute->setLocalized($item['localized']);
            $attribute->setSystem($item['system']);
            $attribute->setRequired($item['required']);
            $attribute->setUnique($item['unique']);
            $attribute->addLabel($label);

            $manager->persist($attribute);
        }

        if (!empty($this->attributes)) {
            $manager->flush();
            $manager->clear();
        }
    }
}

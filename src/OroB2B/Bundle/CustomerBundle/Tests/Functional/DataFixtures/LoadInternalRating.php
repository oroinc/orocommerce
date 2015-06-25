<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class LoadInternalRating extends AbstractFixture
{
    /**
     * Returns an array of possible enum values, where array key is an id and array value is an English translation
     *
     * @return array
     */
    protected function getData()
    {
        return [
            'internal_rating.1 of 5' => 'internal_rating.1 of 5',
            'internal_rating.2 of 5' => 'internal_rating.2 of 5'
        ];
    }

    /**
     * Returns an enum code of an extend entity
     *
     * @return string
     */
    protected function getEnumCode()
    {
        return 'cust_internal_rating';
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $className = ExtendHelper::buildEnumValueClassName($this->getEnumCode());
        /** @var EnumValueRepository $enumRepo */
        $enumRepo = $manager->getRepository($className);

        $priority = 1;
        foreach ($this->getData() as $id => $name) {
            $isDefault = $id === $this->getDefaultValue();
            $enumOption = $enumRepo->createEnumValue($name, $priority++, $isDefault, $id);
            $manager->persist($enumOption);
            $this->addReference($name, $enumOption);
        }

        $manager->flush();
    }

    /**
     * Returns an id of a default enum value
     *
     * @return string|null
     */
    protected function getDefaultValue()
    {
        return null;
    }
}
<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

use OroB2B\Bundle\AccountBundle\Entity\Account;

class LoadInternalRating extends AbstractEnumFixture
{
    /**
     * Returns an array of possible enum values, where array key is an id and array value is an English translation
     *
     * @return array
     */
    protected function getData()
    {
        return [
            'internal_rating.1_of_5' => 'internal_rating.1 of 5',
            'internal_rating.2_of_5' => 'internal_rating.2 of 5'
        ];
    }

    /**
     *
     * Returns an enum code of an extend entity
     *
     * @return string
     */
    protected function getEnumCode()
    {
        return Account::INTERNAL_RATING_CODE;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        parent::load($manager);

        $className = ExtendHelper::buildEnumValueClassName($this->getEnumCode());
        /** @var AbstractEnumValue[] $enumData */
        $enumData = $manager->getRepository($className)->findAll();
        
        foreach ($enumData as $enumItem) {
            $this->addReference($enumItem->getName(), $enumItem);
        }
    }
}

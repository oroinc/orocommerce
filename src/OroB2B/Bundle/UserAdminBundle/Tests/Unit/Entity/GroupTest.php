<?php

namespace OroB2B\Bundle\UserAdminBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\UserAdminBundle\Entity\Group;
use OroB2B\Bundle\UserAdminBundle\Entity\GroupTranslation;

class GroupTest extends EntityTestCase
{
    const GROUP_NAME = 'test';
    const GROUP_LABEL = 'Test label';

    /**
     * Test toString
     */
    public function testToString()
    {
        $value = 'Test group';

        $group = new Group(self::GROUP_NAME);
        $group->setLabel($value);

        $this->assertEquals($value, (string)$group);
    }

    /**
     * Test translation setters getters
     */
    public function testTranslation()
    {
        $group = new Group(self::GROUP_NAME);

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $group->getTranslations());
        $this->assertCount(0, $group->getTranslations());

        $translation = new GroupTranslation();

        $group->addTranslation($translation);

        $this->assertCount(1, $group->getTranslations());

        $group->addTranslation($translation);

        $this->assertCount(1, $group->getTranslations());

        $group->addTranslation(new GroupTranslation());

        $this->assertCount(2, $group->getTranslations());

        $translation = new GroupTranslation();
        $translation
            ->setLocale('en_US')
            ->setField('type');
        $translations = new ArrayCollection([$translation]);
        $group->setTranslations($translations);
        $this->assertCount(1, $group->getTranslations());
    }
}

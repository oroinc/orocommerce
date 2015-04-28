<?php

namespace OroB2B\Bundle\EmailBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\EmailBundle\Entity\EmailTemplate;
use OroB2B\Bundle\EmailBundle\Entity\EmailTemplateTranslation;

class EmailTemplateTest extends EntityTestCase
{
    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $properties = [
            ['name', 'test_template'],
            ['isSystem', true],
            ['isEditable', true],
            ['parent', 1],
            ['subject', 'Test subject'],
            ['content', 'Test content'],
            ['locale', 'en_US'],
            ['entityName', '\OroB2B\Bundle\TestBundle\Entity\Test'],
            ['type', 'html'],
        ];

        $emailTemplate = new EmailTemplate();

        $this->assertPropertyAccessors($emailTemplate, $properties);
    }

    public function testGetId()
    {
        $id = 42;
        $emailTemplate = new EmailTemplate();

        $this->assertNull($emailTemplate->getId());

        $reflection = new \ReflectionProperty(get_class($emailTemplate), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($emailTemplate, $id);

        $this->assertEquals($id, $emailTemplate->getId());
    }

    /**
     * Test toString
     */
    public function testToString()
    {
        $value = 'Test template';

        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName($value);

        $this->assertEquals($value, (string) $emailTemplate);
    }

    /**
     * Test translation setters getters
     */
    public function testTranslation()
    {
        $emailTemplate = new EmailTemplate();

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $emailTemplate->getTranslations());
        $this->assertCount(0, $emailTemplate->getTranslations());

        $translation = new EmailTemplateTranslation();

        $emailTemplate->addTranslation($translation);

        $this->assertCount(1, $emailTemplate->getTranslations());

        $emailTemplate->addTranslation($translation);

        $this->assertCount(1, $emailTemplate->getTranslations());

        $emailTemplate->addTranslation(new EmailTemplateTranslation());

        $this->assertCount(2, $emailTemplate->getTranslations());

        $translation = new EmailTemplateTranslation();
        $translation
            ->setLocale('en_US')
            ->setField('type');

        $translations = new ArrayCollection([$translation]);
        $emailTemplate->setTranslations($translations);
        $this->assertCount(1, $emailTemplate->getTranslations());
    }
}

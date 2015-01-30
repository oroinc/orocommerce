<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\Entity;

use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeDefaultValue;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeLabel;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeOption;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeProperty;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AttributeTest extends \PHPUnit_Framework_TestCase
{
    public function testGetId()
    {
        $attributeId = 1;
        $attribute = new Attribute();
        $this->assertNull($attribute->getId());

        $class = new \ReflectionClass($attribute);
        $prop = $class->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($attribute, $attributeId);

        $this->assertEquals($attributeId, $attribute->getId());
    }

    /**
     * @dataProvider flatPropertiesDataProvider
     * @param string $property
     * @param mixed $value
     */
    public function testGetSet($property, $value)
    {
        $attribute = new Attribute();

        call_user_func_array([$attribute, 'set' . ucfirst($property)], [$value]);

        $this->assertEquals(
            $value,
            call_user_func_array(
                [
                    $attribute,
                    method_exists($attribute, 'get' . ucfirst($property))
                        ? 'get' . ucfirst($property)
                        : 'is' . ucfirst($property)
                ],
                []
            )
        );
    }

    public function flatPropertiesDataProvider()
    {
        return [
            'type'         => ['type', 'testType'],
            'code'         => ['code', 'testCode'],
            'sharing_type' => ['sharingType', 'website'],
            'validation'   => ['validation', 'alphanumeric'],
            'contain_html' => ['containHtml', true],
            'localized'    => ['localized', true],
            'system'       => ['system', false],
            'unique'       => ['unique', false],
            'required'     => ['required', false]
        ];
    }

    public function testAttributeRelations()
    {
        // Create websites
        $firstWebsite = new Website();
        $firstWebsite->setName('First Website');
        $firstWebsite->setUrl('www.first-website.com');

        $secondWebsite = new Website();
        $secondWebsite->setName('Second Website');
        $secondWebsite->setUrl('www.second-website.com');

        $thirdWebsite = new Website();
        $thirdWebsite->setName('Third Website');
        $thirdWebsite->setUrl('www.third-website.com');

        // Create locales
        $localeOne = new Locale();
        $localeOne->setCode('es_MX');

        $localeTwo = new Locale();
        $localeTwo->setCode('en_GB');

        $localeThree = new Locale();
        $localeThree->setCode('en_AU');

        // Create attribute labels
        $labelOne = new AttributeLabel();
        $labelOne->setValue('Attribute 01');
        $labelOne->setLocale($localeOne);

        $labelTwo = new AttributeLabel();
        $labelTwo->setValue('Attribute 02');
        $labelTwo->setLocale($localeTwo);

        $labelThree = new AttributeLabel();
        $labelThree->setValue('Attribute 03');
        $labelThree->setLocale($localeThree);

        // Create attribute
        $attribute = new Attribute();
        $attribute->setType('select');

        // Create attribute properties
        $propertyOne = new AttributeProperty();
        $propertyOne->setAttribute($attribute);
        $propertyOne->setWebsite($firstWebsite);

        $propertyTwo = new AttributeProperty();
        $propertyTwo->setAttribute($attribute);
        $propertyTwo->setWebsite($secondWebsite);

        $propertyThree = new AttributeProperty();
        $propertyThree->setAttribute($attribute);
        $propertyThree->setWebsite($thirdWebsite);

        // Create options
        $optionOne = new AttributeOption();
        $optionOne->setAttribute($attribute);
        $optionOne->setLocale($localeOne);

        $optionTwo = new AttributeOption();
        $optionTwo->setAttribute($attribute);
        $optionTwo->setLocale($localeTwo);

        $optionThree = new AttributeOption();
        $optionThree->setAttribute($attribute);
        $optionThree->setLocale($localeThree);

        // Create default values
        $defaultValueOne = new AttributeDefaultValue();
        $defaultValueOne->setAttribute($attribute);
        $defaultValueOne->setLocale($localeOne);
        $defaultValueOne->setOption($optionOne);

        $defaultValueTwo = new AttributeDefaultValue();
        $defaultValueTwo->setAttribute($attribute);
        $defaultValueTwo->setLocale($localeTwo);
        $defaultValueTwo->setOption($optionTwo);

        $defaultValueThree = new AttributeDefaultValue();
        $defaultValueThree->setAttribute($attribute);
        $defaultValueThree->setLocale($localeThree);
        $defaultValueThree->setOption($optionThree);

        // Reset labels
        $this->assertSame($attribute, $attribute->resetLabels([$labelOne, $labelTwo]));
        $actual = $attribute->getLabels();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$labelOne, $labelTwo], $actual->toArray());

        foreach ($actual as $label) {
            $this->assertContains($label, $attribute->getLabels());
        }

        // Reset properties
        $this->assertSame($attribute, $attribute->resetProperties([$propertyOne, $propertyTwo]));
        $actual = $attribute->getProperties();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$propertyOne, $propertyTwo], $actual->toArray());

        foreach ($actual as $property) {
            $this->assertContains($property, $attribute->getProperties());
        }

        // Reset options
        $this->assertSame($attribute, $attribute->resetOptions([$optionOne, $optionTwo]));
        $actual = $attribute->getOptions();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$optionOne, $optionTwo], $actual->toArray());

        foreach ($actual as $option) {
            $this->assertContains($option, $attribute->getOptions());
        }

        // Reset default values
        $this->assertSame($attribute, $attribute->resetDefaultValues([$defaultValueOne, $defaultValueTwo]));
        $actual = $attribute->getDefaultValues();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$defaultValueOne, $defaultValueTwo], $actual->toArray());

        foreach ($actual as $defaultValue) {
            $this->assertContains($defaultValue, $attribute->getDefaultValues());
        }

        // Add already added label
        $this->assertSame($attribute, $attribute->addLabel($labelTwo));
        $actual = $attribute->getLabels();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$labelOne, $labelTwo], $actual->toArray());

        // Add already added  property
        $this->assertSame($attribute, $attribute->addProperty($propertyTwo));
        $actual = $attribute->getProperties();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$propertyOne, $propertyTwo], $actual->toArray());

        // Add already added  option
        $this->assertSame($attribute, $attribute->addOption($optionTwo));
        $actual = $attribute->getOptions();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$optionOne, $optionTwo], $actual->toArray());

        // Add already added  default value
        $this->assertSame($attribute, $attribute->addDefaultValue($defaultValueTwo));
        $actual = $attribute->getDefaultValues();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$defaultValueOne, $defaultValueTwo], $actual->toArray());

        // Add new label
        $this->assertSame($attribute, $attribute->addLabel($labelThree));
        $actual = $attribute->getLabels();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$labelOne, $labelTwo, $labelThree], $actual->toArray());

        foreach ($actual as $label) {
            $this->assertContains($label, $attribute->getLabels());
        }

        // Add new property
        $this->assertSame($attribute, $attribute->addProperty($propertyThree));
        $actual = $attribute->getProperties();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$propertyOne, $propertyTwo, $propertyThree], $actual->toArray());

        foreach ($actual as $property) {
            $this->assertContains($property, $attribute->getProperties());
        }

        // Add new option
        $this->assertSame($attribute, $attribute->addOption($optionThree));
        $actual = $attribute->getOptions();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$optionOne, $optionTwo, $optionThree], $actual->toArray());

        foreach ($actual as $option) {
            $this->assertContains($option, $attribute->getOptions());
        }

        // Add new default value
        $this->assertSame($attribute, $attribute->addDefaultValue($defaultValueThree));
        $actual = $attribute->getDefaultValues();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$defaultValueOne, $defaultValueTwo, $defaultValueThree], $actual->toArray());

        foreach ($actual as $defaultValue) {
            $this->assertContains($defaultValue, $attribute->getDefaultValues());
        }

        // Remove label
        $this->assertSame($attribute, $attribute->removeLabel($labelOne));
        $actual = $attribute->getLabels();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertContains($labelTwo, $actual->toArray());
        $this->assertContains($labelThree, $actual->toArray());
        $this->assertNotContains($labelOne, $actual->toArray());

        // Remove property
        $this->assertSame($attribute, $attribute->removeProperty($propertyOne));
        $actual = $attribute->getProperties();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertContains($propertyTwo, $actual->toArray());
        $this->assertContains($propertyThree, $actual->toArray());
        $this->assertNotContains($propertyOne, $actual->toArray());

        // Remove option
        $this->assertSame($attribute, $attribute->removeOption($optionOne));
        $actual = $attribute->getOptions();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertContains($optionTwo, $actual->toArray());
        $this->assertContains($optionThree, $actual->toArray());
        $this->assertNotContains($optionOne, $actual->toArray());

        // Remove default value
        $this->assertSame($attribute, $attribute->removeDefaultValue($defaultValueOne));
        $actual = $attribute->getDefaultValues();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertContains($defaultValueTwo, $actual->toArray());
        $this->assertContains($defaultValueThree, $actual->toArray());
        $this->assertNotContains($defaultValueOne, $actual->toArray());
    }
}

<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;
use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeDefaultValue;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeLabel;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeOption;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeProperty;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AttributeTest extends EntityTestCase
{

    public function testProperties()
    {
        $properties = [
            ['id', 1],
            ['type', 'testType'],
            ['code', 'testCode'],
            ['sharingType', 'website'],
            ['validation', 'alphanumeric'],
            ['containHtml', true],
            ['localized', true],
            ['system', false],
            ['unique', false],
            ['required', false],
        ];

        $this->assertPropertyAccessors(new Attribute(), $properties);
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
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

    public function testGetLabelByLocaleId()
    {
        $defaultLabel = new AttributeLabel();
        $defaultLabel->setValue('default');

        $firstLocale = $this->createLocale(1);
        $firstLocaleLabel = new AttributeLabel();
        $firstLocaleLabel->setValue('first')
            ->setLocale($firstLocale);

        $secondLocale = $this->createLocale(2);
        $secondLocaleLabel = new AttributeLabel();
        $secondLocaleLabel->setValue('second')
            ->setLocale($secondLocale);

        $attribute = new Attribute();
        $attribute->resetLabels([$defaultLabel, $firstLocaleLabel, $secondLocaleLabel]);

        $this->assertEquals($defaultLabel, $attribute->getLabelByLocaleId(null));
        $this->assertEquals($firstLocaleLabel, $attribute->getLabelByLocaleId($firstLocale->getId()));
        $this->assertEquals($secondLocaleLabel, $attribute->getLabelByLocaleId($secondLocale->getId()));
        $this->assertNull($attribute->getLabelByLocaleId(42));
    }

    public function testGetDefaultValueByLocaleId()
    {
        $defaultValue = new AttributeDefaultValue();
        $defaultValue->setString('default');

        $firstLocale = $this->createLocale(1);
        $firstLocaleValue = new AttributeDefaultValue();
        $firstLocaleValue->setString('first')
            ->setLocale($firstLocale);

        $secondLocale = $this->createLocale(2);
        $secondLocaleValue = new AttributeDefaultValue();
        $secondLocaleValue->setString('second')
            ->setLocale($secondLocale);

        $attribute = new Attribute();
        $attribute->resetDefaultValues([$defaultValue, $firstLocaleValue, $secondLocaleValue]);

        $this->assertEquals($defaultValue, $attribute->getDefaultValueByLocaleId(null));
        $this->assertEquals($firstLocaleValue, $attribute->getDefaultValueByLocaleId($firstLocale->getId()));
        $this->assertEquals($secondLocaleValue, $attribute->getDefaultValueByLocaleId($secondLocale->getId()));
        $this->assertNull($attribute->getDefaultValueByLocaleId(42));
    }

    public function testGetPropertiesByFieldAndGetPropertyByFieldAndWebsiteId()
    {
        $firstWebsite = $this->createWebsite(1);
        $secondWebsite = $this->createWebsite(2);

        $onViewDefault = new AttributeProperty();
        $onViewDefault->setField(AttributeProperty::FIELD_ON_PRODUCT_VIEW);

        $onViewFirst = new AttributeProperty();
        $onViewFirst->setField(AttributeProperty::FIELD_ON_PRODUCT_VIEW)
            ->setWebsite($firstWebsite);

        $inFiltersDefault = new AttributeProperty();
        $inFiltersDefault->setField(AttributeProperty::FIELD_USE_IN_FILTERS);

        $inFiltersFirst = new AttributeProperty();
        $inFiltersFirst->setField(AttributeProperty::FIELD_USE_IN_FILTERS)
            ->setWebsite($firstWebsite);

        $inFiltersSecond = new AttributeProperty();
        $inFiltersSecond->setField(AttributeProperty::FIELD_USE_IN_FILTERS)
            ->setWebsite($secondWebsite);

        $attribute = new Attribute();
        $attribute->resetProperties(
            [$onViewDefault, $onViewFirst, $inFiltersDefault, $inFiltersFirst, $inFiltersSecond]
        );

        $this->assertEquals(
            [$onViewDefault, $onViewFirst],
            array_values($attribute->getPropertiesByField(AttributeProperty::FIELD_ON_PRODUCT_VIEW)->toArray())
        );
        $this->assertEquals(
            [$inFiltersDefault, $inFiltersFirst, $inFiltersSecond],
            array_values($attribute->getPropertiesByField(AttributeProperty::FIELD_USE_IN_FILTERS)->toArray())
        );
        $this->assertEmpty(
            $attribute->getPropertiesByField(AttributeProperty::FIELD_USE_IN_SORTING)->toArray()
        );

        $this->assertEquals(
            $onViewDefault,
            $attribute->getPropertyByFieldAndWebsiteId(AttributeProperty::FIELD_ON_PRODUCT_VIEW, null)
        );
        $this->assertEquals(
            $inFiltersSecond,
            $attribute->getPropertyByFieldAndWebsiteId(AttributeProperty::FIELD_USE_IN_FILTERS, $secondWebsite->getId())
        );
        $this->assertNull(
            $attribute->getPropertyByFieldAndWebsiteId(AttributeProperty::FIELD_USE_IN_SORTING, $firstWebsite->getId())
        );
        $this->assertNull(
            $attribute->getPropertyByFieldAndWebsiteId(AttributeProperty::FIELD_ON_PRODUCT_VIEW, 42)
        );
    }

    /**
     * @param int $id
     * @return Locale
     */
    protected function createLocale($id)
    {
        $locale = new Locale();

        $reflection = new \ReflectionProperty(get_class($locale), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($locale, $id);

        return $locale;
    }

    /**
     * @param int $id
     * @return Website
     */
    protected function createWebsite($id)
    {
        $locale = new Website();

        $reflection = new \ReflectionProperty(get_class($locale), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($locale, $id);

        return $locale;
    }
}

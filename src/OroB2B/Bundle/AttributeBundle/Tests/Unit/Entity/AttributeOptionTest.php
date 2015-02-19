<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;
use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeOption;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class AttributeOptionTest extends EntityTestCase
{

    public function testProperties()
    {
        $locale = new Locale();
        $locale->setCode('es_MX');

        $attribute = new Attribute();
        $attribute->setType('select');

        $masterOption = new AttributeOption();
        $masterOption->setAttribute($attribute)
            ->setValue('master');

        $properties = [
            ['id', 1],
            ['value', 'test'],
            ['order', 5],
            ['fallback', 'website'],
            ['locale', $locale, false],
            ['locale', null],
            ['attribute', $attribute, false],
            ['masterOption', $masterOption],
            ['masterOption', null],
        ];

        $this->assertPropertyAccessors(new AttributeOption(), $properties);
    }

    public function testAddAndRemoveRelatedOption()
    {
        $masterOption = new AttributeOption();
        $masterOption->setValue('master');

        $firstOption = new AttributeOption();
        $firstOption->setValue('first');

        $secondOption = new AttributeOption();
        $secondOption->setValue('second');

        $thirdOption = new AttributeOption();
        $thirdOption->setValue('third');

        $masterOption->addRelatedOption($firstOption);
        $masterOption->addRelatedOption($firstOption);
        $masterOption->addRelatedOption($secondOption);
        $masterOption->addRelatedOption($thirdOption);
        $masterOption->removeRelatedOption($firstOption);
        $masterOption->removeRelatedOption($firstOption);

        $this->assertEquals([$secondOption, $thirdOption], array_values($masterOption->getRelatedOptions()->toArray()));
    }

    public function testGetRelatedOptionByLocaleId()
    {
        $firstLocale = $this->createLocale(1);
        $secondLocale = $this->createLocale(2);

        $masterOption = new AttributeOption();
        $masterOption->setValue('master');

        $nullOption = new AttributeOption();
        $nullOption->setValue('null');

        $firstOption = new AttributeOption();
        $firstOption->setLocale($firstLocale)
            ->setValue('first');

        $secondOption = new AttributeOption();
        $secondOption->setLocale($secondLocale)
            ->setValue('second');

        $masterOption->addRelatedOption($nullOption)
            ->addRelatedOption($firstOption)
            ->addRelatedOption($secondOption);

        $this->assertEquals($firstOption, $masterOption->getRelatedOptionByLocaleId(1));
        $this->assertEquals($secondOption, $masterOption->getRelatedOptionByLocaleId(2));
        $this->assertEquals($nullOption, $masterOption->getRelatedOptionByLocaleId(null));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Several related attribute options found by the same locale ID.
     */
    public function testGetRelatedOptionByLocaleIdException()
    {
        $locale = $this->createLocale(1);

        $firstOption = new AttributeOption();
        $firstOption->setLocale($locale);

        $secondOption = new AttributeOption();
        $secondOption->setLocale($locale);

        $masterOption = new AttributeOption();
        $masterOption->addRelatedOption($firstOption)
            ->addRelatedOption($secondOption);
        $masterOption->getRelatedOptionByLocaleId(1);
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
}

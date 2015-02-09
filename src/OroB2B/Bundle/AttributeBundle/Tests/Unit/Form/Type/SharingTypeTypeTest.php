<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\AttributeBundle\Model\SharingType;
use OroB2B\Bundle\AttributeBundle\Form\Type\SharingTypeType;

class SharingTypeTypeTest extends FormIntegrationTestCase
{
    /**
     * @var SharingTypeType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new SharingTypeType();
    }

    public function testSubmit()
    {
        $expectedOptions = [
            'empty_value' => false,
            'choices' => [
                SharingType::GENERAL => 'orob2b.attribute.sharing_type.general',
                SharingType::GROUP   => 'orob2b.attribute.sharing_type.group',
                SharingType::WEBSITE => 'orob2b.attribute.sharing_type.website',
            ]
        ];

        $form = $this->factory->create($this->formType);
        $this->assertNull($form->getData());

        $formConfig = $form->getConfig();
        foreach ($expectedOptions as $option => $value) {
            $this->assertTrue($formConfig->hasOption($option));
            $this->assertEquals($value, $formConfig->getOption($option));
        }

        $form->submit(SharingType::WEBSITE);
        $this->assertTrue($form->isValid());
        $this->assertEquals(SharingType::WEBSITE, $form->getData());
    }

    public function testGetName()
    {
        $this->assertEquals(SharingTypeType::NAME, $this->formType->getName());
    }
}

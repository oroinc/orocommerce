<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\TaxBundle\Form\Type\ZipCodeType;
use OroB2B\Bundle\TaxBundle\Tests\Component\ZipCodeTestHelper;

class ZipCodeTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ZipCodeType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new ZipCodeType();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->formType);
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->formType->getName());
        $this->assertEquals('orob2b_tax_zip_code_type', $this->formType->getName());
    }

    /**
     * @dataProvider submitDataProvider
     * @param string $submittedData
     * @param string $expectedData
     */
    public function testSubmit(
        $submittedData,
        $expectedData
    ) {
        $form = $this->factory->create($this->formType);

        $transformers = $form->getConfig()->getModelTransformers();
        $this->assertCount(1, $transformers);
        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Form\DataTransformer\ZipCodeTransformer', $transformers[0]);


        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $expected = [
            ZipCodeTestHelper::getRangeZipCode('00001', '00010')
        ];

        return [
            'datatransformer works' => [
                'submittedData' => '00001-00010',
                'expectedData' => new ArrayCollection($expected),
            ],
        ];
    }
}

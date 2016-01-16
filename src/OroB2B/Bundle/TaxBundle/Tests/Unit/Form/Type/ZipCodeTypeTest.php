<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\TaxBundle\Form\Type\ZipCodeType;
use OroB2B\Bundle\TaxBundle\Tests\Component\ZipCodeTestHelper;

class ZipCodeTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\TaxBundle\Entity\ZipCode';

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
        $this->formType->setDataClass(self::DATA_CLASS);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->formType);

        parent::tearDown();
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
     * @param bool $valid
     */
    public function testSubmit(
        $submittedData,
        $expectedData,
        $valid
    ) {
        $form = $this->factory->create($this->formType);

        $transformers = $form->getConfig()->getModelTransformers();
        $this->assertCount(1, $transformers);
        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Form\DataTransformer\ZipCodeTransformer', $transformers[0]);


        $form->submit($submittedData);
        $this->assertEquals($valid, $form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'different range' => [
                'submittedData' => [
                    'zipRangeStart' => '123',
                    'zipRangeEnd' => '234',
                ],
                'expectedData' => ZipCodeTestHelper::getRangeZipCode('123', '234'),
                'valid' => true,
            ],
            'same range' => [
                'submittedData' => [
                    'zipRangeStart' => '123',
                    'zipRangeEnd' => '123',
                ],
                'expectedData' => ZipCodeTestHelper::getSingleValueZipCode('123'),
                'valid' => true,
            ],
            'start range only' => [
                'submittedData' => [
                    'zipRangeStart' => '123',
                    'zipRangeEnd' => null,
                ],
                'expectedData' => ZipCodeTestHelper::getSingleValueZipCode('123'),
                'valid' => true,
            ],
            'end range only' => [
                'submittedData' => [
                    'zipRangeStart' => null,
                    'zipRangeEnd' => '123',
                ],
                'expectedData' => ZipCodeTestHelper::getSingleValueZipCode('123'),
                'valid' => true,
            ],
            // TODO: should pass test after BB-1957
//            'alphanumeric zip code' => [
//                'submittedData' => [
//                    'zipRangeStart' => '1A30D',
//                    'zipRangeEnd' => '1A32B',
//                ],
//                'expectedData' => null,
//                'valid' => false,
//            ],
        ];
    }
}

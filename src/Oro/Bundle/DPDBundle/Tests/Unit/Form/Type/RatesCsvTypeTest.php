<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Form\Type;

use Oro\Bundle\DPDBundle\Form\Type\RatesCsvType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class RatesCsvTypeTest extends FormIntegrationTestCase
{
    /** @var RatesCsvType */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new RatesCsvType();
    }

    public function testGetBlockPrefix()
    {
        static::assertEquals(RatesCsvType::NAME, $this->formType->getBlockPrefix());
    }

    /**
     * @param array $submittedData
     * @param mixed $expectedData
     * @param mixed $defaultData
     *
     * @dataProvider submitProvider
     */
    public function testSubmit($submittedData, $expectedData, $defaultData = null)
    {
        $form = $this->factory->create($this->formType, $defaultData);

        static::assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        static::assertTrue($form->isValid());
        static::assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'empty default data' => [
                'submittedData' => null,
                'expectedData' => null,
            ],
            'full data' => [
                'submittedData' => $this->createUploadedFileMock('filename', 'original_filename', true),
                'expectedData' => $this->createUploadedFileMock('filename', 'original_filename', true),
                'defaultData' => null,
            ],
        ];
    }

    private function createUploadedFileMock($name, $originalName, $valid)
    {
        $file = $this
            ->getMockBuilder('Symfony\Component\HttpFoundation\File\UploadedFile')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $file
            ->expects($this->any())
            ->method('getBasename')
            ->will($this->returnValue($name))
        ;
        $file
            ->expects($this->any())
            ->method('getClientOriginalName')
            ->will($this->returnValue($originalName))
        ;
        $file
            ->expects($this->any())
            ->method('isValid')
            ->will($this->returnValue($valid))
        ;

        return $file;
    }
}

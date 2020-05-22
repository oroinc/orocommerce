<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationRequestHandler;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class QuickAddImportFromFileTypeTest extends FormIntegrationTestCase
{
    /**
     * @var QuickAddImportFromFileType
     */
    protected $formType;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->formType = new QuickAddImportFromFileType();

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension([], []),
            $this->getValidatorExtension(true)
        ];
    }

    /**
     * @dataProvider submitDataProvider
     * @param array $data
     * @param array $expectedData
     * @param bool $isValid
     */
    public function testSubmit(array $data, array $expectedData, $isValid)
    {
        $formBuilder = $this->factory->createBuilder(QuickAddImportFromFileType::class);
        $formBuilder->get(QuickAddImportFromFileType::FILE_FIELD_NAME)
            ->setRequestHandler(new HttpFoundationRequestHandler());
        $form = $formBuilder->getForm();

        $form->submit($data);
        $this->assertEquals($isValid, $form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $invalidFile = $this->createUploadedFile('quick-order.doc');
        $validFile = $this->createUploadedFile('quick-order.csv');

        return [
            'null' => [
                'data' => [
                    QuickAddImportFromFileType::FILE_FIELD_NAME => null
                ],
                'expectedData' => [
                    QuickAddImportFromFileType::FILE_FIELD_NAME => null
                ],
                'isValid' => false
            ],
            'invalid value' => [
                'data' => [
                    QuickAddImportFromFileType::FILE_FIELD_NAME => 'abcdef'
                ],
                'expectedData' => [
                    QuickAddImportFromFileType::FILE_FIELD_NAME => null
                ],
                'isValid' => false
            ],
            'invalid file' => [
                'data' => [
                    QuickAddImportFromFileType::FILE_FIELD_NAME => $invalidFile
                ],
                'expectedData' => [
                    QuickAddImportFromFileType::FILE_FIELD_NAME => $invalidFile
                ],
                'isValid' => false
            ],
            'valid file' => [
                'data' => [
                    QuickAddImportFromFileType::FILE_FIELD_NAME => $validFile
                ],
                'expectedData' => [
                    QuickAddImportFromFileType::FILE_FIELD_NAME => $validFile
                ],
                'isValid' => true
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        unset($this->formType);
    }

    /**
     * @param string $fileName
     * @return UploadedFile
     */
    private function createUploadedFile($fileName)
    {
        return new UploadedFile(__DIR__ . '/files/' . $fileName, $fileName, null, null, null, true);
    }
}

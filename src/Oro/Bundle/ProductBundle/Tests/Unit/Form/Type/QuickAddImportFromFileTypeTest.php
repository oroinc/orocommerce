<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;
use Oro\Bundle\ProductBundle\Validator\Constraints\QuickAddComponentProcessorValidator;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationRequestHandler;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class QuickAddImportFromFileTypeTest extends FormIntegrationTestCase
{
    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([], []),
            $this->getValidatorExtension(true),
        ];
    }

    #[\Override]
    protected function getValidators(): array
    {
        $quickAddComponentProcessorValidator = $this->createMock(QuickAddComponentProcessorValidator::class);

        return [
            QuickAddComponentProcessorValidator::class => $quickAddComponentProcessorValidator,
        ];
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $data, array $expectedData, bool $isValid): void
    {
        $formBuilder = $this->factory->createBuilder(QuickAddImportFromFileType::class);
        $formBuilder->get(QuickAddImportFromFileType::FILE_FIELD_NAME)
            ->setRequestHandler(new HttpFoundationRequestHandler());
        $form = $formBuilder->getForm();

        $form->submit($data);
        self::assertEquals($isValid, $form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        $invalidFile = $this->createUploadedFile('quick-order.doc');
        $validFile = $this->createUploadedFile('quick-order.csv');

        return [
            'null' => [
                'data' => [
                    QuickAddImportFromFileType::FILE_FIELD_NAME => null,
                    QuickAddImportFromFileType::COMPONENT_FIELD_NAME => null,
                ],
                'expectedData' => [
                    QuickAddImportFromFileType::FILE_FIELD_NAME => null,
                    QuickAddImportFromFileType::COMPONENT_FIELD_NAME => null,
                ],
                'isValid' => false,
            ],
            'invalid value' => [
                'data' => [
                    QuickAddImportFromFileType::FILE_FIELD_NAME => 'abcdef',
                    QuickAddImportFromFileType::COMPONENT_FIELD_NAME => null,
                ],
                'expectedData' => [
                    QuickAddImportFromFileType::FILE_FIELD_NAME => null,
                    QuickAddImportFromFileType::COMPONENT_FIELD_NAME => null,
                ],
                'isValid' => false,
            ],
            'invalid file' => [
                'data' => [
                    QuickAddImportFromFileType::FILE_FIELD_NAME => $invalidFile,
                    QuickAddImportFromFileType::COMPONENT_FIELD_NAME => null,
                ],
                'expectedData' => [
                    QuickAddImportFromFileType::FILE_FIELD_NAME => $invalidFile,
                    QuickAddImportFromFileType::COMPONENT_FIELD_NAME => null,
                ],
                'isValid' => false,
            ],
            'valid file' => [
                'data' => [
                    QuickAddImportFromFileType::FILE_FIELD_NAME => $validFile,
                    QuickAddImportFromFileType::COMPONENT_FIELD_NAME => 'test',
                ],
                'expectedData' => [
                    QuickAddImportFromFileType::FILE_FIELD_NAME => $validFile,
                    QuickAddImportFromFileType::COMPONENT_FIELD_NAME => 'test',
                ],
                'isValid' => true,
            ],
        ];
    }

    private function createUploadedFile(string $fileName): UploadedFile
    {
        return new UploadedFile(__DIR__ . '/files/' . $fileName, $fileName, null, null, true);
    }
}

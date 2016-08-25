<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;

class QuickAddCopyPasteTypeTest extends FormIntegrationTestCase
{
    /**
     * @var QuickAddCopyPasteType
     */
    protected $formType;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->formType = new QuickAddCopyPasteType();

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
     * @param bool $isValid
     */
    public function testSubmit(array $data, $isValid)
    {
        $form = $this->factory->create($this->formType);

        $form->submit($data);
        $this->assertEquals($isValid, $form->isValid());

        $formData = $form->getData();

        $this->assertEquals($data, $formData);
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'empty string' => [
                'data' => [
                    QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME => '',
                ],
                'isValid' => false
            ],
            'invalid string' => [
                'data' => [
                    QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME => 'abcdef',
                ],
                'isValid' => false
            ],
            'valid string with comma separator' => [
                'data' => [
                    QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME => <<<TEXT
ABC, 1
DEF, 4.5
TEXT
                ],
                'isValid' => true
            ],
            'valid string with tab separator' => [
                'data' => [
                    QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME => <<<TEXT
ABC	1
DEF	4.5
TEXT
                ],
                'isValid' => true
            ],
            'valid string with space separator' => [
                'data' => [
                    QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME => <<<TEXT
ABC 1
DEF 4.5
TEXT
                ],
                'isValid' => true
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->formType);
    }
}

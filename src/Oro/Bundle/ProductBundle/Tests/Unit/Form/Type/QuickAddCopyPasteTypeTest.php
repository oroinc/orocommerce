<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class QuickAddCopyPasteTypeTest extends FormIntegrationTestCase
{
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
        $form = $this->factory->create(QuickAddCopyPasteType::class);

        $form->submit($data);
        $this->assertEquals($isValid, $form->isValid());
        $this->assertTrue($form->isSynchronized());

        $formData = $form->getData();

        $this->assertEquals($data, $formData);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitDataProvider()
    {
        return [
            'empty string' => [
                'data' => [
                    QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME => '',
                ],
                'isValid' => true
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
ABC,1,item
DEF,4.5,item
TEXT
                ],
                'isValid' => true
            ],
            'valid string with tab separator' => [
                'data' => [
                    QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME => <<<TEXT
ABC	1	item
DEF	4.5	item
TEXT
                ],
                'isValid' => true
            ],
            'valid string with space separator' => [
                'data' => [
                    QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME => <<<TEXT
ABC 1 item
DEF 4.5 item
TEXT
                ],
                'isValid' => true
            ],
            'valid comma separated string without optional field' => [
                'data' => [
                    QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME => <<<TEXT
ABC,1,item
DEC,1
DEF,4.5,item
TEXT
                ],
                'isValid' => true
            ],
            'valid semicolon separated string without optional field' => [
                'data' => [
                    QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME => <<<TEXT
ABC;1;item
DEC;1
DEF;4.5;item
TEXT
                ],
                'isValid' => true
            ],
            'valid tab separated string without optional field' => [
                'data' => [
                    QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME => <<<TEXT
ABC	1	item
DEC	1
DEF	4.5	item
TEXT
                ],
                'isValid' => true
            ],
            'tab separated string with negative quantity' => [
                'data' => [
                    QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME => <<<TEXT
ABC	-1	item
DEC	-1
DEF	-4.5	item
TEXT
                ],
                'isValid' => false
            ],
            'semicolon separated string with negative quantity' => [
                'data' => [
                    QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME => <<<TEXT
ABC;-1;item
DEC;-1
DEF;-4.5;item
TEXT
                ],
                'isValid' => false
            ],
            'string with space separator and negative quantity' => [
                'data' => [
                    QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME => <<<TEXT
ABC -1 item
DEF -4.5 item
TEXT
                ],
                'isValid' => false
            ],
            'multiple rows in one line' => [
                'data' => [
                    QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME => <<<TEXT
tag1 1 itemtag1 1 itemtag1 1 item
TEXT
                ],
                'isValid' => false
            ],
        ];
    }
}

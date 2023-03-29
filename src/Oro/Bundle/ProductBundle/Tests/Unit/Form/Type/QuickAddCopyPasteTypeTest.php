<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class QuickAddCopyPasteTypeTest extends FormIntegrationTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([], []),
            $this->getValidatorExtension(true)
        ];
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $data, bool $isValid, array $parsed = [])
    {
        $form = $this->factory->create(QuickAddCopyPasteType::class);

        $form->submit($data);
        $this->assertTrue($form->isSynchronized());
        $this->assertSame($isValid, $form->isValid());
        $this->assertEquals($data, $form->getData());

        // test the item parse pattern
        if ($isValid && $data[QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME] && $parsed) {
            $itemParsePattern = $form->get(QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME)
                ->getConfig()
                ->getOption('attr')['data-item-parse-pattern'];
            $dataRows = explode("\n", $data[QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME]);
            $parsedItems = [];
            foreach ($dataRows as $dataRow) {
                $dataRow = trim($dataRow, "\r");
                self::assertSame(1, preg_match($itemParsePattern, $dataRow, $matches), $dataRow);
                $parsedItems[] = [$matches['sku'], $matches['quantity'], $matches['unit'] ?? null];
            }
            self::assertEquals($parsed, $parsedItems);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitDataProvider(): array
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
                'isValid' => true,
                'parsed' => [['ABC', '1', 'item'], ['DEF', '4.5', 'item']]
            ],
            'valid string with tab separator' => [
                'data' => [
                    QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME => <<<TEXT
ABC	1	item
DEF	4.5	item
TEXT
                ],
                'isValid' => true,
                'parsed' => [['ABC', '1', 'item'], ['DEF', '4.5', 'item']]
            ],
            'valid string with space separator' => [
                'data' => [
                    QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME => <<<TEXT
ABC 1 item
DEF 4.5 item
TEXT
                ],
                'isValid' => true,
                'parsed' => [['ABC', '1', 'item'], ['DEF', '4.5', 'item']]
            ],
            'valid comma separated string without optional field' => [
                'data' => [
                    QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME => <<<TEXT
ABC,1,item
DEC,1
DEF,4.5,item
TEXT
                ],
                'isValid' => true,
                'parsed' => [['ABC', '1', 'item'], ['DEC', '1', null], ['DEF', '4.5', 'item']]
            ],
            'valid semicolon separated string without optional field' => [
                'data' => [
                    QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME => <<<TEXT
ABC;1;item
DEC;1
DEF;4.5;item
TEXT
                ],
                'isValid' => true,
                'parsed' => [['ABC', '1', 'item'], ['DEC', '1', null], ['DEF', '4.5', 'item']]
            ],
            'valid tab separated string without optional field' => [
                'data' => [
                    QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME => <<<TEXT
ABC	1	item
DEC	1
DEF	4.5	item
TEXT
                ],
                'isValid' => true,
                'parsed' => [['ABC', '1', 'item'], ['DEC', '1', null], ['DEF', '4.5', 'item']]
            ],
            'tab separated string with negative quantity' => [
                'data' => [
                    QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME => <<<TEXT
ABC	-1	item
DEC	-1
DEF	-4.5	item
TEXT
                ],
                'isValid' => false,
                'parsed' => [['ABC', '-1', 'item'], ['DEC', '-1', null], ['DEF', '-4.5', 'item']]
            ],
            'semicolon separated string with negative quantity' => [
                'data' => [
                    QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME => <<<TEXT
ABC;-1;item
DEC;-1
DEF;-4.5;item
TEXT
                ],
                'isValid' => false,
                'parsed' => [['ABC', '-1', 'item'], ['DEC', '-1', null], ['DEF', '-4.5', 'item']]
            ],
            'string with space separator and negative quantity' => [
                'data' => [
                    QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME => <<<TEXT
ABC -1 item
DEF -4.5 item
TEXT
                ],
                'isValid' => false,
                'parsed' => [['ABC', '-1', 'item'], ['DEF', '-4.5', 'item']]
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

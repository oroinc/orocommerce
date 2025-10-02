<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\QuickAdd\Normalizer;

use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection as ModelQuickAddRowCollection;
use Oro\Bundle\ProductBundle\QuickAdd\Normalizer\QuickAddCollectionIssuesNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class QuickAddCollectionIssuesNormalizerTest extends TestCase
{
    private TranslatorInterface&MockObject $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
    }

    public function testNormalize(): void
    {
        $quickAddRowCollection = new ModelQuickAddRowCollection();
        $quickAddRow = new QuickAddRow(0, 'not_existing_sku', 12);
        $quickAddRow->addError('not_exising_product', [], 'product');
        $quickAddRow->addWarning('not_enough_quantity', [], 'quantity');
        $quickAddRowCollection->add($quickAddRow);

        $normalizer = new QuickAddCollectionIssuesNormalizer(
            $this->translator
        );

        $this->translator->expects(self::exactly(2))
            ->method('trans')
            ->willReturnMap(
                [
                    [
                        'not_exising_product',
                        [
                            '{{ index }}' => 0,
                            '{{ sku }}' => 'not_existing_sku'
                        ],
                        'validators',
                        null,
                        'not_exising_product_message'
                    ],
                    [
                        'not_enough_quantity',
                        [
                            '{{ index }}' => 0,
                            '{{ sku }}' => 'not_existing_sku'
                        ],
                        'validators',
                        null,
                        'not_enough_quantity_message'
                    ]
                ]
            );

        $collectionWithIssues = $normalizer->normalize($quickAddRowCollection);

        self::assertCount(1, $collectionWithIssues);
        self::assertEquals(0, $collectionWithIssues[0]['index']);
        self::assertCount(1, $collectionWithIssues[0]['errors']);
        self::assertCount(1, $collectionWithIssues[0]['warnings']);

        self::assertEquals('not_exising_product_message', $collectionWithIssues[0]['errors'][0]['message']);
        self::assertEquals('product', $collectionWithIssues[0]['errors'][0]['propertyPath']);
        self::assertEquals('not_enough_quantity_message', $collectionWithIssues[0]['warnings'][0]['message']);
        self::assertEquals('quantity', $collectionWithIssues[0]['warnings'][0]['propertyPath']);
    }
}

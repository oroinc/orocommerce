<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeFamilyManager;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\ProductDataConverterEventListener;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductDataConverterEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var AttributeFamilyManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeFamilyManager;

    /** @var AttributeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeManager;

    /** @var ImportStrategyHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $strategyHelper;

    /** @var ProductDataConverterEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                static function (string $key, array $params) {
                    return sprintf('%s: %s', $key, \json_encode($params));
                }
            );

        $this->attributeFamilyManager = $this->createMock(AttributeFamilyManager::class);

        $this->attributeManager = $this->createMock(AttributeManager::class);
        $this->attributeManager->expects($this->any())
            ->method('getAttributeLabel')
            ->willReturnCallback(
                static function (FieldConfigModel $attribute) {
                    return $attribute->getFieldName() . 'Label';
                }
            );

        $this->strategyHelper = $this->createMock(ImportStrategyHelper::class);

        $this->listener = new ProductDataConverterEventListener(
            $this->translator,
            $this->attributeFamilyManager,
            $this->attributeManager,
            $this->strategyHelper,
        );
    }

    public function testOnConvertToImportEmptyData(): void
    {
        $event = new ProductDataConverterEvent();

        $this->listener->onConvertToImport($event);

        $this->assertEquals([], $event->getData());
    }

    public function testOnConvertToImportNoContext(): void
    {
        $event = new ProductDataConverterEvent(['attributeFamily' => ['code' => 'test']]);

        $this->listener->onConvertToImport($event);

        $this->assertEquals(['attributeFamily' => ['code' => 'test']], $event->getData());
    }

    public function testOnConvertToImportNoAttributeFamily(): void
    {
        $event = new ProductDataConverterEvent(['attributeFamily' => ['code' => 'test']]);
        $event->setContext($this->createMock(ContextInterface::class));

        $this->listener->onConvertToImport($event);

        $this->assertEquals(['attributeFamily' => ['code' => 'test']], $event->getData());
    }

    public function testOnConvertToImport(): void
    {
        $context = $this->createMock(ContextInterface::class);

        $event = new ProductDataConverterEvent(
            [
                'attributeFamily' => ['code' => 'test'],
                'attribute2' => [],
                'attribute3' => '',
                'attribute4' => 'data4',
                'attribute5' => 'data5',
            ]
        );
        $event->setContext($context);

        $attributeFamily = new AttributeFamily();
        $attributeFamily->setCode('test');

        $this->attributeFamilyManager->expects($this->once())
            ->method('getAttributeFamilyByCode')
            ->with('test')
            ->willReturn($attributeFamily);

        $attribute1 = new FieldConfigModel();

        $attribute2 = new FieldConfigModel();
        $attribute2->setFieldName('attribute2');

        $attribute3 = new FieldConfigModel();
        $attribute3->setFieldName('attribute3');

        $attribute4 = new FieldConfigModel();
        $attribute4->setFieldName('attribute4');

        $attribute5 = new FieldConfigModel();
        $attribute5->setFieldName('attribute5');

        $this->attributeManager->expects($this->once())
            ->method('getActiveAttributesByClass')
            ->with(Product::class)
            ->willReturn([$attribute1, $attribute2, $attribute3, $attribute4, $attribute5]);

        $this->attributeManager->expects($this->exactly(2))
            ->method('getAttributeByFamilyAndName')
            ->willReturnMap(
                [
                    [$attributeFamily, $attribute4->getFieldName(), null],
                    [$attributeFamily, $attribute5->getFieldName(), $attribute5],
                ]
            );

        $this->strategyHelper->expects($this->once())
            ->method('addValidationErrors')
            ->with(
                [
                    'oro.product.attribute_family.ignored_attributes.message: ' .
                    '{"%count%":1,"%attributes%":"attribute4Label"}'
                ],
                $context,
                'oro.importexport.import.warning: {"%number%":0}'
            );

        $this->listener->onConvertToImport($event);

        $this->assertEquals(
            [
                'attributeFamily' => ['code' => 'test'],
                'attribute2' => [],
                'attribute3' => '',
                'attribute5' => 'data5',
            ],
            $event->getData()
        );
    }
}

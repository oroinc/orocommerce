<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ProductVariant\TypeHandler;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ProductVariant\TypeHandler\EnumTypeHandler;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EnumTypeHandlerTest extends WebTestCase
{
    const FIELD_NAME = 'test_field';

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var EnumTypeHandler */
    private $handler;

    /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $qb;

    protected function setUp(): void
    {
        $this->initClient();

        $container = $this->getContainer();
        $class = Product::class;

        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configManager->expects($this->any())
            ->method('getConfigFieldModel')
            ->with($class, self::FIELD_NAME)
            ->willReturn($this->getFieldConfigModel());

        $this->handler = new EnumTypeHandler($container->get('form.factory'), $class, $this->configManager);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->any())
            ->method('execute')
            ->willReturn(
                [
                    $this->getItem('10 mm'),
                    $this->getItem('10'),
                    $this->getItem('10mm'),
                ]
            );

        $this->qb = $this->createMock(QueryBuilder::class);
        $this->qb->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);
    }

    public function testCreateForm()
    {
        $form = $this->handler->createForm(
            self::FIELD_NAME,
            [
                '10 mm' => true,
                '10' => false,
                '10mm' => true,
            ],
            [
                'query_builder' => $this->qb
            ]
        );

        $formView = $form->createView();

        foreach ($formView->vars['choices'] as $choice) {
            $attr = [];

            if ($choice->value === '10') {
                $attr = ['disabled' => 'disabled'];
            }

            $this->assertEquals($attr, $choice->attr);
        }
    }

    /**
     * @return FieldConfigModel
     */
    private function getFieldConfigModel()
    {
        $model = new FieldConfigModel(self::FIELD_NAME);
        $model->fromArray('extend', ['target_entity' => Item::class]);

        return $model;
    }

    /**
     * @param string $name
     * @return Item
     */
    private function getItem($name)
    {
        $item = new Item();
        $item->id = $name;
        $item->name = $name;

        return $item;
    }
}

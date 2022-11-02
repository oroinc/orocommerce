<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Type\ShoppingListNotesType;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ShoppingListNotesTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var ShoppingListNotesType */
    private $type;

    protected function setUp(): void
    {
        $this->type = new ShoppingListNotesType();

        parent::setUp();
    }

    public function testGetBlockPrefix(): void
    {
        $this->assertEquals('oro_shopping_list_notes_type', $this->type->getBlockPrefix());
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(?ShoppingList $defaultData, array $submittedData, ShoppingList $expectedData): void
    {
        $form = $this->factory->create(ShoppingListNotesType::class, $defaultData);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        $this->assertCount(0, $form->getErrors(true));
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        return [
            'new shopping list' => [
                'defaultData' => null,
                'submittedData' => [
                    'notes' => 'new notes',
                ],
                'expectedData' => $this->getEntity(ShoppingList::class, ['notes' => 'new notes']),
            ],
            'existing shopping list' => [
                'defaultData' => $this->getEntity(ShoppingList::class, ['id' => 42, 'notes' => 'existing notes']),
                'submittedData' => [
                    'notes' => 'updated notes',
                ],
                'expectedData' => $this->getEntity(ShoppingList::class, ['id' => 42, 'notes' => 'updated notes']),
            ],
        ];
    }
}

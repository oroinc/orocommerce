<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Type\ShoppingListType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShoppingListTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'Oro\Bundle\ShoppingListBundle\Entity\ShoppingList';

    /**
     * @var ShoppingListType
     */
    protected $type;

    protected function setUp(): void
    {
        $this->type = new ShoppingListType();
        $this->type->setDataClass(self::DATA_CLASS);
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    ShoppingListType::class => $this->type
                ],
                []
            ),
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(ShoppingListType::class);

        $this->assertTrue($form->has('label'));
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param mixed $defaultData
     * @param mixed $submittedData
     * @param mixed $expectedData
     */
    public function testSubmit($defaultData, $submittedData, $expectedData)
    {
        $form = $this->factory->create(ShoppingListType::class, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        $this->assertEmpty($form->getErrors(true)->count());
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $expectedShoppingList = new ShoppingList();
        $expectedShoppingList
            ->setLabel('new label');

        $existingShoppingList = $this->getEntity(ShoppingList::class, 1);
        $existingShoppingList
            ->setLabel('existing label');

        $expectedShoppingList2 = $this->getEntity(ShoppingList::class, 1);
        $expectedShoppingList2
            ->setLabel('updated label');

        return [
            'new shopping list'      => [
                'defaultData'   => null,
                'submittedData' => [
                    'label' => 'new label',
                ],
                'expectedData'  => $expectedShoppingList,
            ],
            'existing shopping list' => [
                'defaultData'   => $existingShoppingList,
                'submittedData' => [
                    'label' => 'updated label',
                ],
                'expectedData'  => $expectedShoppingList2,
            ],
        ];
    }

    public function testConfigureOptions()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|OptionsResolver $resolver */
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['data_class' => self::DATA_CLASS]);

        $this->type->configureOptions($resolver);
    }

    /**
     * @param string $className
     * @param int    $id
     *
     * @return object
     */
    protected function getEntity($className, $id)
    {
        $entity = new $className;

        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);

        return $entity;
    }
}

<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\ShoppingListType;

class ShoppingListTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList';

    /**
     * @var ShoppingListType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new ShoppingListType();
        $this->type->setDataClass(self::DATA_CLASS);
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->type);

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
        $form = $this->factory->create($this->type, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        $this->assertEmpty($form->getErrors()->count());
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $defaultShoppingList = new ShoppingList();

        $expectedShoppingList = clone $defaultShoppingList;
        $expectedShoppingList
            ->setLabel('new label');

        $existingShoppingList = $this->getEntity('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', 1);
        $existingShoppingList
            ->setLabel('existing label');

        $expectedShoppingList2 = clone $existingShoppingList;
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
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolver $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['data_class' => self::DATA_CLASS]);

        $this->type->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(ShoppingListType::NAME, $this->type->getName());
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

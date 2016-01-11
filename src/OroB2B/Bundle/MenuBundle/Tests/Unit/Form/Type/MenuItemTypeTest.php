<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\Form\Extension\Stub\FormTypeValidatorExtensionStub;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType as StubEntityIdentifierType;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedFallbackValueCollectionType;
use OroB2B\Bundle\FallbackBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use OroB2B\Bundle\MenuBundle\Entity\MenuItem;
use OroB2B\Bundle\MenuBundle\Form\Type\MenuItemType;

class MenuItemTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\MenuBundle\Entity\MenuItem';

    /**
     * @var MenuItemType
     */
    protected $formType;

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    LocalizedFallbackValueCollectionType::NAME => new LocalizedFallbackValueCollectionTypeStub(),
                    EntityIdentifierType::NAME => new StubEntityIdentifierType([]),
                ],
                ['form' => [new FormTypeValidatorExtensionStub()]]
            )
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new MenuItemType();
        $this->formType->setDataClass(static::DATA_CLASS);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->formType);

        parent::tearDown();
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->formType->getName());
        $this->assertEquals('orob2b_menu_item', $this->formType->getName());
    }

    public function atestBuildFormForRootMenuItem()
    {
        $form = $this->factory->create($this->formType);

        $this->assertTrue($form->has('defaultTitle'));
    }

    public function testBuildFormForRegularMenuItem()
    {
        $menuItem = new MenuItem();
        $menuItem->setParentMenuItem(new MenuItem());
        $form = $this->factory->create($this->formType, $menuItem);

        $this->assertTrue($form->has('parentMenuItem'));
        $this->assertTrue($form->has('titles'));
        $this->assertTrue($form->has('uri'));
    }

    /**
     * @dataProvider submitDataProvider
     * @param bool $isValid
     * @param mixed $defaultData
     * @param mixed $viewData
     * @param array $submittedData
     * @param array $expectedData
     */
    public function testSubmit(
        $isValid,
        $defaultData,
        $viewData,
        array $submittedData,
        $expectedData
    ) {
        $form = $this->factory->create($this->formType, $defaultData);

        $formConfig = $form->getConfig();
        $this->assertEquals(static::DATA_CLASS, $formConfig->getOption('data_class'));

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($viewData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertEquals($isValid, $form->isValid());

        foreach ($expectedData as $field => $data) {
            $this->assertTrue($form->has($field));
            $fieldForm = $form->get($field);
            $this->assertEquals($data, $fieldForm->getData());
        }
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $root = new MenuItem();
        $menuItem = new MenuItem();
        $menuItem->setDefaultTitle('menu_item_title');
        $menuItem->setUri('uri');
        $menuItem->setParentMenuItem($root);

        return [
            'root menu item' => [
                'isValid' => true,
                'defaultData' => $root,
                'viewData' => $root,
                'submittedData' => [
                    'defaultTitle' => 'new_menu_item_title',
                ],
                'expectedData' => [
                    'defaultTitle' => 'new_menu_item_title',
                ]
            ],
            'regular menu item' => [
                'isValid' => true,
                'defaultData' => $menuItem,
                'viewData' => $menuItem,
                'submittedData' => [
                    'titles' => [['string'=>'new_menu_item_title']],
                    'uri' => 'new_uri',
                ],
                'expectedData' => [
                    'titles' => new ArrayCollection(
                        [(new LocalizedFallbackValue())->setString('new_menu_item_title')]
                    ),
                    'uri' => 'new_uri',
                ]
            ],

        ];
    }
}

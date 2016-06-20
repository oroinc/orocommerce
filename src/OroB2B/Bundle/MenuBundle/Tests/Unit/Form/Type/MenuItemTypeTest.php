<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Form\PreloadedExtension;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Component\Testing\Unit\Form\Extension\Stub\FormTypeValidatorExtensionStub;

use OroB2B\Bundle\MenuBundle\Tests\Unit\Form\Extension\Stub\TooltipFormExtensionStub;
use OroB2B\Bundle\MenuBundle\Tests\Unit\Form\Extension\Stub\ImageTypeStub;
use OroB2B\Bundle\MenuBundle\Tests\Unit\Entity\Stub\MenuItemStub;
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
                    ImageType::NAME => new ImageTypeStub()
                ],
                ['form' => [new FormTypeValidatorExtensionStub(), new TooltipFormExtensionStub()]]
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

    public function testBuildFormForRootMenuItem()
    {
        $form = $this->factory->create($this->formType);

        $this->assertTrue($form->has('defaultTitle'));
    }

    public function testBuildFormForRegularMenuItem()
    {
        $menuItem = new MenuItemStub();
        $menuItem->setParent(new MenuItemStub());
        $form = $this->factory->create($this->formType, $menuItem);

        $this->assertTrue($form->has('titles'));
        $this->assertTrue($form->has('uri'));
        $this->assertTrue($form->has('condition'));
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
        $root = new MenuItemStub();
        $menuItem = new MenuItemStub();
        $menuItem->setDefaultTitle('menu_item_title');
        $menuItem->setUri('uri');
        $menuItem->setCondition('1 > 2');
        $menuItem->setParent($root);

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
                    'condition' => '1 > 2'
                ],
                'expectedData' => [
                    'titles' => new ArrayCollection(
                        [(new LocalizedFallbackValue())->setString('new_menu_item_title')]
                    ),
                    'uri' => 'new_uri',
                    'condition' => '1 > 2'
                ]
            ],

        ];
    }
}

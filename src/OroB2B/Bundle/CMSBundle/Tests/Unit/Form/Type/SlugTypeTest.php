<?php

namespace OroB2B\Bundle\CMSBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\CMSBundle\Form\Type\SlugType;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\ConstraintViolationList;

class SlugTypeTest extends FormIntegrationTestCase
{
    const NAME = 'orob2b_slug';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @var SlugType
     */
    protected $formType;

    protected function setUp()
    {
        /**
         * @var \Symfony\Component\Validator\ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject $validator
         */
        $validator = $this->getMock('\Symfony\Component\Validator\ValidatorInterface');
        $validator->expects($this->any())
            ->method('validate')
            ->will($this->returnValue(new ConstraintViolationList()));

        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->addTypeExtension(new FormTypeValidatorExtension($validator))
            ->getFormFactory();

        $this->formType = new SlugType();
    }

    /**
     * @param array $options
     * @param mixed $defaultData
     * @param mixed $viewData
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $options, $defaultData, $viewData, $submittedData, $expectedData)
    {
        $form = $this->factory->create($this->formType, $defaultData, $options);

        $formConfig = $form->getConfig();
        if (empty($options['type'])) {
            $this->assertEquals('create', $formConfig->getOption('type'));
        }
        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($viewData, $form->getViewData());

        if (empty($options['type'])) {
            $this->assertEquals('create', $form->createView()->vars['type']);
        } else {
            $this->assertEquals($options['type'], $form->createView()->vars['type']);
        }

        if (empty($options['current_slug'])) {
            $this->assertEquals('', $form->createView()->vars['current_slug']);
        } else {
            $this->assertEquals($options['current_slug'], $form->createView()->vars['current_slug']);
        }

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'new slug' => [
                'options' => [
                ],
                'defaultData' => [],
                'viewData' => [],
                'submittedData' => [
                    'slug' => 'new_slug',
                    'mode' => 'new'
                ],
                'expectedData' => [
                    'slug' => 'new_slug',
                    'mode' => 'new'
                ],
            ],
            'update current slug' => [
                'options' => [
                    'type' => 'update',
                    'current_slug' => 'existing_slug'
                ],
                'defaultData' => [
                    'mode' => 'old',
                    'slug' => 'existing_slug',
                    'redirect' => false
                ],
                'viewData' => [
                    'mode' => 'old',
                    'slug' => 'existing_slug',
                    'redirect' => false
                ],
                'submittedData' => [
                    'mode' => 'old',
                    'slug' => 'updated_slug',
                    'redirect' => false
                ],
                'expectedData' => [
                    'mode' => 'old',
                    'slug' => 'updated_slug',
                    'redirect' => false
                ],
            ],
            'update current slug with redirect' => [
                'options' => [
                    'type' => 'update',
                    'current_slug' => 'existing_slug'
                ],
                'defaultData' => [
                    'mode' => 'old',
                    'slug' => 'existing_slug',
                    'redirect' => false
                ],
                'viewData' => [
                    'mode' => 'old',
                    'slug' => 'existing_slug',
                    'redirect' => false
                ],
                'submittedData' => [
                    'mode' => 'old',
                    'slug' => 'updated_slug',
                    'redirect' => true
                ],
                'expectedData' => [
                    'mode' => 'old',
                    'slug' => 'updated_slug',
                    'redirect' => true
                ],
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(SlugType::NAME, $this->formType->getName());
    }
}

<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\TaxBundle\Form\Type\AbstractTaxCodeType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

abstract class AbstractTaxCodeTypeTest extends FormIntegrationTestCase
{
    /**
     * @var AbstractTaxCodeType
     */
    protected $formType;

    protected function setUp(): void
    {
        $this->formType = $this->createTaxCodeType();
        $this->formType->setDataClass($this->getDataClass());
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
                    get_class($this->formType) => $this->formType
                ],
                []
            ),
        ];
    }

    protected function tearDown(): void
    {
        unset($this->formType);

        parent::tearDown();
    }

    /**
     * @dataProvider submitDataProvider
     * @param mixed $defaultData
     * @param mixed $viewData
     * @param array $submittedData
     * @param array $expectedData
     */
    public function testSubmit(
        $defaultData,
        $viewData,
        array $submittedData,
        $expectedData
    ) {
        $form = $this->factory->create(get_class($this->formType), $defaultData);

        $this->assertTrue($form->has('code'));
        $this->assertTrue($form->has('description'));

        $formConfig = $form->getConfig();
        $this->assertEquals($this->getDataClass(), $formConfig->getOption('data_class'));

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($viewData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

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
        return [
            'empty description' => [
                'defaultData' => null,
                'viewData' => null,
                'submittedData' => [
                    'code' => 'SomeCode',
                    'description' => ''
                ],
                'expectedData' => [
                    'code' => 'SomeCode',
                    'description' => ''
                ]
            ],
            'filled description' => [
                'defaultData' => null,
                'viewData' => null,
                'submittedData' => [
                    'code' => 'SomeCode',
                    'description' => 'description'
                ],
                'expectedData' => [
                    'code' => 'SomeCode',
                    'description' => 'description'
                ]
            ],
        ];
    }

    /**
     * Return data class string
     *
     * @return string
     */
    abstract protected function getDataClass();

    /**
     * Return object of test form type
     *
     * @return AbstractTaxCodeType
     */
    abstract protected function createTaxCodeType();
}

<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\PricingBundle\Form\Type\ProductPriceCollectionType;

class ProductPriceCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductPriceCollectionType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new ProductPriceCollectionType('\stdClass');
    }

    protected function tearDown()
    {
        unset($this->formType);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $collection = new CollectionType();

        return [
            new PreloadedExtension(
                [
                    $collection->getName() => $collection
                ],
                []
            )
        ];
    }

    /**
     * @param array $options
     * @param array $defaultData
     * @param array $viewData
     * @param array $submittedData
     * @param array $expectedData
     *
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        array $options,
        array $defaultData,
        array $viewData,
        array $submittedData,
        array $expectedData
    ) {
        $form = $this->factory->create($this->formType, $defaultData, $options);

        $formConfig = $form->getConfig();
        $this->assertNull($formConfig->getOption('data_class'));

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($viewData, $form->getViewData());

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
            'default' => [
                'options' => [],
                'defaultData' => [],
                'viewData' => [],
                'submittedData' => [],
                'expectedData' => []
            ]
        ];
    }
}

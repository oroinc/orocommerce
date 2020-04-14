<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\TaxBundle\Form\Type\CustomerTaxCodeAutocompleteType;
use Oro\Bundle\TaxBundle\Form\Type\ProductTaxCodeAutocompleteType;
use Oro\Bundle\TaxBundle\Form\Type\TaxJurisdictionSelectType;
use Oro\Bundle\TaxBundle\Form\Type\TaxRuleType;
use Oro\Bundle\TaxBundle\Form\Type\TaxSelectType;
use Oro\Bundle\TaxBundle\Form\Type\TaxType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class TaxRuleTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    const DATA_CLASS = 'Oro\Bundle\TaxBundle\Entity\TaxRule';

    /**
     * @var TaxType
     */
    protected $formType;

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $productTaxCodeAutocomplete = new EntityType(
            [
                1 => $this->getEntity('Oro\Bundle\TaxBundle\Entity\ProductTaxCode', ['id' => 1]),
                2 => $this->getEntity('\Oro\Bundle\TaxBundle\Entity\ProductTaxCode', ['id' => 2])
            ],
            ProductTaxCodeAutocompleteType::NAME
        );

        $customerTaxCodeAutocomplete = new EntityType(
            [
                1 => $this->getEntity('Oro\Bundle\TaxBundle\Entity\CustomerTaxCode', ['id' => 1]),
                2 => $this->getEntity('\Oro\Bundle\TaxBundle\Entity\CustomerTaxCode', ['id' => 2])
            ],
            CustomerTaxCodeAutocompleteType::NAME
        );

        $taxSelect = new EntityType(
            [
                1 => $this->getEntity('Oro\Bundle\TaxBundle\Entity\Tax', ['id' => 1]),
                2 => $this->getEntity('\Oro\Bundle\TaxBundle\Entity\Tax', ['id' => 2])
            ],
            TaxSelectType::NAME
        );

        $taxJurisdictionSelect = new EntityType(
            [
                1 => $this->getEntity('Oro\Bundle\TaxBundle\Entity\TaxJurisdiction', ['id' => 1]),
                2 => $this->getEntity('\Oro\Bundle\TaxBundle\Entity\TaxJurisdiction', ['id' => 2])
            ],
            TaxJurisdictionSelectType::NAME
        );

        return [
            new PreloadedExtension(
                [
                    TaxRuleType::class => $this->formType,
                    CustomerTaxCodeAutocompleteType::class => $customerTaxCodeAutocomplete,
                    ProductTaxCodeAutocompleteType::class => $productTaxCodeAutocomplete,
                    TaxSelectType::class => $taxSelect,
                    TaxJurisdictionSelectType::class => $taxJurisdictionSelect
                ],
                []
            )
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->formType = new TaxRuleType();
        $this->formType->setDataClass(static::DATA_CLASS);
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset($this->formType);

        parent::tearDown();
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(TaxRuleType::class);

        $this->assertTrue($form->has('description'));
        $this->assertTrue($form->has('customerTaxCode'));
        $this->assertTrue($form->has('productTaxCode'));
        $this->assertTrue($form->has('tax'));
    }

    /**
     * @dataProvider submitDataProvider
     * @param array   $options
     * @param mixed  $defaultData
     * @param mixed  $viewData
     * @param array  $submittedData
     * @param array  $expectedData
     */
    public function testSubmit(
        $options,
        $defaultData,
        $viewData,
        array $submittedData,
        $expectedData
    ) {
        $form = $this->factory->create(TaxRuleType::class, $defaultData, $options);

        $formConfig = $form->getConfig();
        $this->assertEquals(static::DATA_CLASS, $formConfig->getOption('data_class'));

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
            'set customer tax code' => [
                'options' => [],
                'defaultData' => null,
                'viewData' => null,
                'submittedData' => [
                    'description' => 'description',
                    'customerTaxCode' => 1,
                    'productTaxCode' => null,
                    'tax' => 1,
                    'taxJurisdiction' => 1
                ],
                'expectedData' => [
                    'description' => 'description',
                    'customerTaxCode' => $this->getEntity('Oro\Bundle\TaxBundle\Entity\CustomerTaxCode', ['id' => 1]),
                    'productTaxCode' => null,
                    'tax' => $this->getEntity('Oro\Bundle\TaxBundle\Entity\Tax', ['id' => 1]),
                    'taxJurisdiction' =>
                        $this->getEntity('Oro\Bundle\TaxBundle\Entity\TaxJurisdiction', ['id' => 1]),
                ]
            ],
            'set product tax code' => [
                'options' => [],
                'defaultData' => null,
                'viewData' => null,
                'submittedData' => [
                    'description' => 'description product tax code',
                    'customerTaxCode' => null,
                    'productTaxCode' => 1,
                    'tax' => 2,
                    'taxJurisdiction' => 2
                ],
                'expectedData' => [
                    'description' => 'description product tax code',
                    'customerTaxCode' => null,
                    'productTaxCode' => $this->getEntity('Oro\Bundle\TaxBundle\Entity\ProductTaxCode', ['id' => 1]),
                    'tax' => $this->getEntity('Oro\Bundle\TaxBundle\Entity\Tax', ['id' => 2]),
                    'taxJurisdiction' =>
                        $this->getEntity('Oro\Bundle\TaxBundle\Entity\TaxJurisdiction', ['id' => 2]),
                ]
            ]
        ];
    }
}

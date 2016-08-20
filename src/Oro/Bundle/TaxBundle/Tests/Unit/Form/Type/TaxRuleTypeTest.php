<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Bundle\TaxBundle\Form\Type\TaxRuleType;
use Oro\Bundle\TaxBundle\Form\Type\TaxType;
use Oro\Bundle\TaxBundle\Form\Type\AccountTaxCodeAutocompleteType;
use Oro\Bundle\TaxBundle\Form\Type\ProductTaxCodeAutocompleteType;
use Oro\Bundle\TaxBundle\Form\Type\TaxSelectType;
use Oro\Bundle\TaxBundle\Form\Type\TaxJurisdictionSelectType;

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

        $accountTaxCodeAutocomplete = new EntityType(
            [
                1 => $this->getEntity('Oro\Bundle\TaxBundle\Entity\AccountTaxCode', ['id' => 1]),
                2 => $this->getEntity('\Oro\Bundle\TaxBundle\Entity\AccountTaxCode', ['id' => 2])
            ],
            AccountTaxCodeAutocompleteType::NAME
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
                    AccountTaxCodeAutocompleteType::NAME => $accountTaxCodeAutocomplete,
                    ProductTaxCodeAutocompleteType::NAME => $productTaxCodeAutocomplete,
                    TaxSelectType::NAME => $taxSelect,
                    TaxJurisdictionSelectType::NAME => $taxJurisdictionSelect
                ],
                []
            )
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new TaxRuleType();
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
        $this->assertEquals(TaxRuleType::NAME, $this->formType->getName());
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->formType);

        $this->assertTrue($form->has('description'));
        $this->assertTrue($form->has('accountTaxCode'));
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
        $form = $this->factory->create($this->formType, $defaultData, $options);

        $formConfig = $form->getConfig();
        $this->assertEquals(static::DATA_CLASS, $formConfig->getOption('data_class'));

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($viewData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

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
            'set account tax code' => [
                'options' => [],
                'defaultData' => null,
                'viewData' => null,
                'submittedData' => [
                    'description' => 'description',
                    'accountTaxCode' => 1,
                    'productTaxCode' => null,
                    'tax' => 1,
                    'taxJurisdiction' => 1
                ],
                'expectedData' => [
                    'description' => 'description',
                    'accountTaxCode' => $this->getEntity('Oro\Bundle\TaxBundle\Entity\AccountTaxCode', ['id' => 1]),
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
                    'accountTaxCode' => null,
                    'productTaxCode' => 1,
                    'tax' => 2,
                    'taxJurisdiction' => 2
                ],
                'expectedData' => [
                    'description' => 'description product tax code',
                    'accountTaxCode' => null,
                    'productTaxCode' => $this->getEntity('Oro\Bundle\TaxBundle\Entity\ProductTaxCode', ['id' => 1]),
                    'tax' => $this->getEntity('Oro\Bundle\TaxBundle\Entity\Tax', ['id' => 2]),
                    'taxJurisdiction' =>
                        $this->getEntity('Oro\Bundle\TaxBundle\Entity\TaxJurisdiction', ['id' => 2]),
                ]
            ]
        ];
    }
}

<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Form\Type\CustomerTaxCodeAutocompleteType;
use Oro\Bundle\TaxBundle\Form\Type\ProductTaxCodeAutocompleteType;
use Oro\Bundle\TaxBundle\Form\Type\TaxJurisdictionSelectType;
use Oro\Bundle\TaxBundle\Form\Type\TaxRuleType;
use Oro\Bundle\TaxBundle\Form\Type\TaxSelectType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class TaxRuleTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    private TaxRuleType $formType;

    protected function setUp(): void
    {
        $this->formType = new TaxRuleType();
        $this->formType->setDataClass(TaxRule::class);
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    CustomerTaxCodeAutocompleteType::class => new EntityTypeStub([
                        1 => $this->getEntity(CustomerTaxCode::class, ['id' => 1]),
                        2 => $this->getEntity(CustomerTaxCode::class, ['id' => 2])
                    ]),
                    ProductTaxCodeAutocompleteType::class => new EntityTypeStub([
                        1 => $this->getEntity(ProductTaxCode::class, ['id' => 1]),
                        2 => $this->getEntity(ProductTaxCode::class, ['id' => 2])
                    ]),
                    TaxSelectType::class => new EntityTypeStub([
                        1 => $this->getEntity(Tax::class, ['id' => 1]),
                        2 => $this->getEntity(Tax::class, ['id' => 2])
                    ]),
                    TaxJurisdictionSelectType::class => new EntityTypeStub([
                        1 => $this->getEntity(TaxJurisdiction::class, ['id' => 1]),
                        2 => $this->getEntity(TaxJurisdiction::class, ['id' => 2])
                    ])
                ],
                []
            )
        ];
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
     */
    public function testSubmit(
        array $options,
        mixed $defaultData,
        mixed $viewData,
        array $submittedData,
        array $expectedData
    ) {
        $form = $this->factory->create(TaxRuleType::class, $defaultData, $options);

        $formConfig = $form->getConfig();
        $this->assertEquals(TaxRule::class, $formConfig->getOption('data_class'));

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

    public function submitDataProvider(): array
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
                    'customerTaxCode' => $this->getEntity(CustomerTaxCode::class, ['id' => 1]),
                    'productTaxCode' => null,
                    'tax' => $this->getEntity(Tax::class, ['id' => 1]),
                    'taxJurisdiction' =>
                        $this->getEntity(TaxJurisdiction::class, ['id' => 1]),
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
                    'productTaxCode' => $this->getEntity(ProductTaxCode::class, ['id' => 1]),
                    'tax' => $this->getEntity(Tax::class, ['id' => 2]),
                    'taxJurisdiction' =>
                        $this->getEntity(TaxJurisdiction::class, ['id' => 2]),
                ]
            ]
        ];
    }
}

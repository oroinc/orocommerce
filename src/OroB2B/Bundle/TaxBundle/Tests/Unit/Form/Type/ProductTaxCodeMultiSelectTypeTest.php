<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\Form\Type\Stub\FormStub;

use OroB2B\Bundle\TaxBundle\Form\Type\ProductTaxCodeMultiSelectType;

class ProductTaxCodeMultiSelectTypeTest extends FormIntegrationTestCase
{
    /** @var ProductTaxCodeMultiSelectType */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new ProductTaxCodeMultiSelectType();

        parent::setUp();
    }

    protected function tearDown()
    {
        unset($this->formType);

        parent::tearDown();
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_product_tax_code_multiselect', $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_jqueryselect2_hidden', $this->formType->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertArrayHasKey('autocomplete_alias', $options);
        $this->assertEquals('orob2b_product_tax_code', $options['autocomplete_alias']);
        $this->assertArrayHasKey('configs', $options);
        $this->assertEquals(['multiple' => true], $options['configs']);
    }

    /**
     * @dataProvider buildViewDataProvider
     * @param object|null $data
     * @param bool $expected
     */
    public function testBuildView($data, $expected)
    {
        $view = new FormView();
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */

        $form = $this->factory->create($this->formType);
        $form->setData($data);
        $this->formType->buildView($view, $form, []);

        $this->assertEquals($expected, $view->vars['attr']);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    'oro_jqueryselect2_hidden' => new FormStub('oro_jqueryselect2_hidden'),
                ],
                []
            ),
        ];
    }

    /**
     * @return array
     */
    public function buildViewDataProvider()
    {
        return [
            [null, ['data-selected-data' => '[]']],
            [[], ['data-selected-data' => '[]']],
            ['', ['data-selected-data' => '[]']],
            ['string', ['data-selected-data' => '[]']],
            [0, ['data-selected-data' => '[0]']],
            ['0', ['data-selected-data' => '[0]']],
            ['1', ['data-selected-data' => '[1]']],
            [[0, 1, '0', '1', 's'], ['data-selected-data' => '[0,1,0,1]']],
        ];
    }
}

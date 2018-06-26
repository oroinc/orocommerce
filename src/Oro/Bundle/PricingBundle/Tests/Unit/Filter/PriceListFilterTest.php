<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Filter;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\PricingBundle\Filter\PriceListFilter;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class PriceListFilterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|FormInterface
     */
    protected $form;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|FilterUtility
     */
    protected $filterUtility;

    /**
     * @var PriceListFilter
     */
    protected $priceListFilter;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->formFactory
            ->expects(static::any())
            ->method('create')
            ->willReturn($this->form);

        $this->filterUtility = $this->createMock(FilterUtility::class);
        $this->filterUtility
            ->expects(static::any())
            ->method('getExcludeParams')
            ->willReturn([]);

        $this->priceListFilter = new PriceListFilter($this->formFactory, $this->filterUtility);
    }

    /**
     * @dataProvider getMetadataDataProvider
     *
     * @param array $formViewVars
     * @param array $expectedMetadata
     */
    public function testGetMetadata(array $formViewVars, array $expectedMetadata)
    {
        $formView = new FormView();
        $valueFormView = new FormView();
        $typeFormView = new FormView();
        $formView->children = ['value' => $valueFormView, 'type' => $typeFormView];
        $formView->vars = $formViewVars;
        $typeFormView->vars = ['choices' => []];
        $valueFormView->vars = [
            'multiple' => false,
            'choices' => [],
        ];

        $this->form
            ->expects(static::exactly(3))
            ->method('createView')
            ->willReturn($formView);

        $metadata = $this->priceListFilter->getMetadata();

        static::assertSame($expectedMetadata, $metadata);
    }

    /**
     * @return array
     */
    public function getMetadataDataProvider()
    {
        return [
            'if required, then allowClear is not allowed' => [
                'formViewVars' => ['required' => true, 'populate_default' => false, 'value' => ['value' => 1]],
                'expectedMetadata' => [
                    'name' => null,
                    'label' => '',
                    'choices' => [],
                    'lazy' => false,
                    'populateDefault' => false,
                    'allowClear' => false,
                    'value' => ['value' => '1'],
                ],
            ],
            'if not required, then allowClear is allowed' => [
                'formViewVars' => ['required' => false, 'populate_default' => false, 'value' => ['value' => 1]],
                'expectedMetadata' => [
                    'name' => null,
                    'label' => '',
                    'choices' => [],
                    'lazy' => false,
                    'populateDefault' => false,
                    'allowClear' => true,
                    'value' => ['value' => '1'],
                ],
            ],
            'if required is not set, then allowClear is not allowed' => [
                'formViewVars' => ['populate_default' => false, 'value' => ['value' => 1]],
                'expectedMetadata' => [
                    'name' => null,
                    'label' => '',
                    'choices' => [],
                    'lazy' => false,
                    'populateDefault' => false,
                    'allowClear' => false,
                    'value' => ['value' => '1'],
                ],
            ],
            'if required is not set, value is not set' => [
                'formViewVars' => ['populate_default' => false],
                'expectedMetadata' => [
                    'name' => null,
                    'label' => '',
                    'choices' => [],
                    'lazy' => false,
                    'populateDefault' => false,
                    'allowClear' => false,
                ],
            ],
        ];
    }
}

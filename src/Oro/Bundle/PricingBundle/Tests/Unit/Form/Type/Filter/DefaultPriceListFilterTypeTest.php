<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EntityFilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractTypeTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Form\Type\Filter\DefaultPriceListFilterType;
use Oro\Bundle\PricingBundle\Provider\PriceListProvider;

class DefaultPriceListFilterTypeTest extends AbstractTypeTestCase
{
    /**
     * @var PriceListProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $provider;

    /**
     * @var DefaultPriceListFilterType
     */
    private $type;

    protected function setUp()
    {
        $translator = $this->createMockTranslator();
        $this->provider = $this->getMockBuilder(PriceListProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->type = new DefaultPriceListFilterType($translator, $this->provider);

        parent::setUp();
    }

    /**
     * @return EntityFilterType
     */
    protected function getTestFormType()
    {
        return $this->type;
    }

    public function testGetName()
    {
        $this->assertEquals(DefaultPriceListFilterType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceFilterType::NAME, $this->type->getParent());
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptionsDataProvider()
    {
        return [
            [
                'defaultOptions' => [
                    'field_type' => 'entity',
                    'field_options' => [],
                    'translatable'  => false,
                ]
            ]
        ];
    }

    /**
     * @dataProvider setDefaultOptionsDataProvider
     * @param array $defaultOptions
     * @param array $requiredOptions
     */
    public function testSetDefaultOptions(array $defaultOptions, array $requiredOptions = [])
    {
        $defaultPriceList = $this->createMock(PriceList::class);
        $defaultPriceList->method('getName')->willReturn('Default Price List');
        $this->provider->method('getDefaultPriceList')
            ->willReturn($defaultPriceList);
        $resolver = $this->createMockOptionsResolver();
        if ($defaultOptions) {
            $resolver->expects($this->exactly(2))
                ->method('setDefaults')
                ->willReturnMap(
                    [
                        [$defaultOptions, $this->returnSelf()],
                        [['default_value' => 'Default Price List'], $this->returnSelf()]
                    ]
                )
                ->will($this->returnSelf());
        }

        if ($requiredOptions) {
            $resolver->expects($this->once())->method('setRequired')->with($requiredOptions)->will($this->returnSelf());
        }

        $this->getTestFormType()->setDefaultOptions($resolver);
    }

    /**
     * @dataProvider bindDataProvider
     * @param array $bindData
     * @param array $formData
     * @param array $viewData
     * @param array $customOptions
     */
    public function testBindData(
        array $bindData,
        array $formData,
        array $viewData,
        array $customOptions = array()
    ) {
        // bind method should be tested in functional test
    }

    /**
     * {@inheritDoc}
     */
    public function bindDataProvider()
    {
        return array(
            'empty' => array(
                'bindData' => array(),
                'formData' => array(),
                'viewData' => array(),
            ),
        );
    }
}

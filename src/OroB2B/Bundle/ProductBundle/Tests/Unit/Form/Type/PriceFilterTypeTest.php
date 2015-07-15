<?php

namespace OroB2B\Bundle\ProductBundle\Unit\Form\Type;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Fixtures\CustomFormExtension;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractTypeTestCase;

use OroB2B\Bundle\ProductBundle\Form\Type\PriceFilterType;

class PriceFilterTypeTest extends AbstractTypeTestCase
{
    /**
     * @var PriceFilterType
     */
    private $type;

    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * @var string
     */
    protected $defaultLocale = 'en';

    protected function setUp()
    {
        $translator             = $this->createMockTranslator();
        $this->formExtensions[] = new CustomFormExtension([new FilterType($translator)]);

        parent::setUp();

        $formatter = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $formatter->method('format')->with('item')
            ->will($this->returnValue('Item'));

        $this->type = new PriceFilterType($this->getObjectManager(), $formatter);
    }
    public function getObjectManager()
    {
        if (!$this->manager) {
            $productUnitMock = $this->getMock('OroB2B\Bundle\ProductBundle\Entity\ProductUnit');
            $productUnitMock->method('getCode')
                ->will($this->returnValue('item'));

            $productUnitRepository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
            $productUnitRepository->method('findAll')
                ->will($this->returnValue([$productUnitMock]));

            $this->manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
            $this->manager->method('getRepository')->with($this->equalTo('OroB2BProductBundle:ProductUnit'))
                ->will($this->returnValue($productUnitRepository));
        }

        return $this->manager;
    }

    /**
     * {@inheritDoc}
     */
    protected function getTestFormType()
    {
        return $this->type;
    }

    public function testGetName()
    {
        $this->assertEquals(PriceFilterType::NAME, $this->type->getName());
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptionsDataProvider()
    {
        return [
            [
                'defaultOptions' => [
                    'field_type'        => 'number',
                    'operator_choices'  => [
                        'item'          => 'Item',
                    ],
                    'formatter_options' => [],
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function bindDataProvider()
    {
        return [
            'not formatted price' => [
                'bindData' => ['type' => 'item', 'value' => '12345.67890'],
                'formData' => ['type' => 'item', 'value' => 12345.6789],
                'viewData' => [
                    'value' => ['type' => 'item', 'value' => '12,345.68'],
                ],
                'customOptions' => [
                    'field_options' => ['grouping' => true, 'precision' => 2],
                ],
            ],
            'formatted price' => [
                'bindData' => ['type' => 'item', 'value' => '12,345.68'],
                'formData' => ['type' => 'item', 'value' => 12345.68],
                'viewData' => [
                    'value' => ['type' => 'item', 'value' => '12,345.68'],
                ],
                'customOptions' => [
                    'field_options' => ['grouping' => true, 'precision' => 2],
                ],
            ],
            'invalid format' => [
                'bindData' => ['type' => 'item', 'value' => 'abcd.67890'],
                'formData' => ['type' => 'item'],
                'viewData' => [
                    'value' => ['type' => 'item', 'value' => 'abcd.67890'],
                ],
                'customOptions' => [
                    'field_options' => ['grouping' => true, 'precision' => 2],
                ],
            ],
        ];
    }
}

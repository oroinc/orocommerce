<?php

namespace OroB2B\Bundle\ProductBundle\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Fixtures\CustomFormExtension;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\Filter\NumberFilterTypeTest;

use OroB2B\Bundle\ProductBundle\Form\Type\PriceFilterType;

class PriceFilterTypeTest extends NumberFilterTypeTest
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    protected function setUp()
    {
        $translator = $this->createMockTranslator();
        $this->formExtensions[] = new CustomFormExtension([new NumberFilterType($translator)]);

        parent::setUp();

        $formatter = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $formatter->expects($this->any())
            ->method('format')
            ->with('item')
            ->will($this->returnValue('Item'));

        $this->type = new PriceFilterType($this->getRegistry(), $formatter);
    }

    public function getRegistry()
    {
        if (!$this->registry) {
            $productUnitMock = $this->getMock('OroB2B\Bundle\ProductBundle\Entity\ProductUnit');
            $productUnitMock->expects($this->any())
                ->method('getCode')
                ->will($this->returnValue('item'));

            $productUnitRepository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
            $productUnitRepository->expects($this->any())
                ->method('findAll')
                ->will($this->returnValue([$productUnitMock]));

            $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
            $this->registry->expects($this->any())
                ->method('getManagerForClass')
                ->with($this->equalTo('OroB2BProductBundle:ProductUnit'))
                ->will($this->returnValue($productUnitRepository));
        }

        return $this->registry;
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
                'defaultOptions' => [],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function bindDataProvider()
    {
        $bindData = parent::bindDataProvider();

        foreach ($bindData as $key => $data) {
            $data['bindData']['unit'] = 'item';
            $data['formData']['unit'] = 'item';
            $data['viewData']['value']['unit'] = 'item';
        }

        return $bindData;
    }
}

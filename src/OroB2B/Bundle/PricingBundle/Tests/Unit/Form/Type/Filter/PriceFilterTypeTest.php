<?php

namespace OroB2B\Bundle\PricingBundle\Unit\Form\Type\Filter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Fixtures\CustomFormExtension;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\Filter\NumberFilterTypeTest;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

use OroB2B\Bundle\PricingBundle\Form\Type\Filter\PriceFilterType;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class PriceFilterTypeTest extends NumberFilterTypeTest
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $translator = $this->createMockTranslator();
        $this->formExtensions[] = new CustomFormExtension([new NumberFilterType($translator)]);

        parent::setUp();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ProductUnitLabelFormatter $formatter */
        $formatter = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $formatter->expects($this->any())
            ->method('format')
            ->with('item')
            ->will($this->returnValue('Item'));

        $this->type = new PriceFilterType($translator, $this->getRegistry(), $formatter);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    public function getRegistry()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ProductUnit $productUnitMock */
        $productUnitMock = $this->getMock('OroB2B\Bundle\ProductBundle\Entity\ProductUnit');
        $productUnitMock->expects($this->any())
            ->method('getCode')
            ->will($this->returnValue('item'));

        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectRepository $productUnitRepository */
        $productUnitRepository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $productUnitRepository->expects($this->any())
            ->method('findAll')
            ->will($this->returnValue([$productUnitMock]));

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager $entityManager */
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BProductBundle:ProductUnit')
            ->will($this->returnValue($productUnitRepository));

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroB2BProductBundle:ProductUnit')
            ->will($this->returnValue($entityManager));

        return $this->registry;
    }

    /**
     * {@inheritDoc}
     */
    public function testGetName()
    {
        $this->assertEquals(PriceFilterType::NAME, $this->type->getName());
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptionsDataProvider()
    {
        return array(
            array(
                'defaultOptions' => array(
                    'data_type'         => NumberFilterType::DATA_DECIMAL,
                    'operator_choices'  => array(
                        NumberFilterType::TYPE_EQUAL         => 'oro.filter.form.label_type_equal',
                        NumberFilterType::TYPE_NOT_EQUAL     => 'oro.filter.form.label_type_not_equal',
                        NumberFilterType::TYPE_GREATER_EQUAL => 'oro.filter.form.label_type_greater_equal',
                        NumberFilterType::TYPE_GREATER_THAN  => 'oro.filter.form.label_type_greater_than',
                        NumberFilterType::TYPE_LESS_EQUAL    => 'oro.filter.form.label_type_less_equal',
                        NumberFilterType::TYPE_LESS_THAN     => 'oro.filter.form.label_type_less_than',
                    ),
                )
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function bindDataProvider()
    {
        $bindData = parent::bindDataProvider();

        foreach ($bindData as $key => &$data) {
            $data['bindData']['unit'] = 'item';
            $data['formData']['unit'] = 'item';
            $data['viewData']['value']['unit'] = 'item';
        }

        return $bindData;
    }

    public function testGetParent()
    {
        $this->assertEquals(NumberFilterType::NAME, $this->type->getParent());
    }
}

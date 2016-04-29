<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use OroB2B\Bundle\ProductBundle\Formatter\UnitLabelFormatter;
use OroB2B\Bundle\ShippingBundle\Form\Type\AbstractShippingOptionSelectType;
use OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type\Stub\ShippingOptionSelectTypeStub;

class AbstractShippingOptionSelectTypeTest extends FormIntegrationTestCase
{
    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var UnitLabelFormatter|\PHPUnit_Framework_MockObject_MockObject */
    protected $formatter;

    /** @var AbstractShippingOptionSelectType */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formatter = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\UnitLabelFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new ShippingOptionSelectTypeStub($this->repository, $this->formatter);
    }

    protected function tearDown()
    {
        unset($this->formType, $this->repository, $this->formatter);

        parent::tearDown();
    }

    public function testGetName()
    {
        $this->assertEquals(AbstractShippingOptionSelectType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->formType->getParent());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @param array $options
     */
    public function testSubmit($submittedData, $expectedData, array $options = [])
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn([
                $this->createUnit('kg'),
                $this->createUnit('lbs')
            ]);

        $this->formatter->expects($this->at(0))
            ->method('format')
            ->with('kg', false, false)
            ->willReturn('formatted.kg');
        $this->formatter->expects($this->at(1))
            ->method('format')
            ->with('lbs', false, false)
            ->willReturn('formatted.lbs');

        $form = $this->factory->create($this->formType, null, $options);

        $this->assertNull($form->getData());

        $formConfig = $form->getConfig();
        $this->assertTrue($formConfig->hasOption('choices'));
        $this->assertEquals(['kg' => 'formatted.kg', 'lbs' => 'formatted.lbs'], $formConfig->getOption('choices'));

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
            [
                'submittedData' => null,
                'expectedData' => null
            ],
            [
                'submittedData' => 'lbs',
                'expectedData' => 'lbs'
            ],
            [
                'submittedData' => ['lbs', 'kg'],
                'expectedData' => ['lbs', 'kg'],
                'options' => ['multiple' => true]
            ],
        ];
    }

    /**
     * @param string $code
     * @return MeasureUnitInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createUnit($code)
    {
        /** @var MeasureUnitInterface|\PHPUnit_Framework_MockObject_MockObject $unit */
        $unit = $this->getMock('OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface');
        $unit->expects($this->atLeastOnce())
            ->method('getCode')
            ->willReturn($code);

        return $unit;
    }
}

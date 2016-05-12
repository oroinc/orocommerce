<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use OroB2B\Bundle\ShippingBundle\Form\Type\AbstractShippingOptionSelectType;
use OroB2B\Bundle\ShippingBundle\Provider\AbstractMeasureUnitProvider;

abstract class AbstractShippingOptionSelectTypeTest extends FormIntegrationTestCase
{
    /** @var AbstractMeasureUnitProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $unitProvider;

    /** @var AbstractShippingOptionSelectType */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->unitProvider = $this->getMockBuilder('OroB2B\Bundle\ShippingBundle\Provider\AbstractMeasureUnitProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        unset($this->formType, $this->repository, $this->formatter, $this->configManager);

        parent::tearDown();
    }

    public function testGetName()
    {
        $this->assertEquals(AbstractShippingOptionSelectType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('entity', $this->formType->getParent());
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
        $this->unitProvider->expects($this->once())
            ->method('getUnitsCodes')
            ->with(empty($options['full_list']))
            ->willReturn(['kg', 'lbs']);

        $this->unitProvider->expects($this->once())
            ->method('formatUnitsCodes')
            ->with(['kg' => 'kg', 'lbs' => 'lbs'], false)
            ->willReturn([
                'kg' => 'formatted.kg',
                'lbs' => 'formatted.lbs',
            ]);

        $form = $this->factory->create($this->formType, null, $options);

        $precision1 = new ProductUnitPrecision();
        $unit1 = new ProductUnit();
        $unit1->setCode('test01');
        $precision1->setUnit($unit1);
        $precision2 = new ProductUnitPrecision();
        $unit2 = new ProductUnit();
        $unit2->setCode('test02');
        $precision2->setUnit($unit2);

        $productUnitHolder = $this->createProductUnitHolder(
            1,
            'sku',
            $unit1,
            $this->createProductHolder(
                1,
                'sku',
                (new Product())->addUnitPrecision($precision1)->addUnitPrecision($precision2)
            )
        );

        $formParent = $this->factory->create(new ProductUnitHolderTypeStub(), $productUnitHolder);
        $form->setParent($formParent);

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
                'expectedData' => null,
            ],
            [
                'submittedData' => 'lbs',
                'expectedData' => 'lbs',
            ],
            [
                'submittedData' => ['lbs', 'kg'],
                'expectedData' => ['lbs', 'kg'],
                'options' => ['multiple' => true],
            ],
            [
                'submittedData' => 'lbs',
                'expectedData' => 'lbs',
                'options' => ['full_list' => true],
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

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    'entity' => new EntityType([]),
                    ProductUnitSelectionType::NAME => new ProductUnitSelectionTypeStub(
                        [1],
                        ProductUnitSelectionType::NAME
                    )
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    /**
     * @param int $id
     * @param $productUnitCode
     * @param ProductUnit $productUnit
     * @param ProductHolderInterface $productHolder
     * @return ProductUnitHolderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createProductUnitHolder(
        $id,
        $productUnitCode,
        ProductUnit $productUnit = null,
        ProductHolderInterface $productHolder = null
    ) {
        /* @var $productUmitHolder \PHPUnit_Framework_MockObject_MockObject|ProductUnitHolderInterface */
        $productUnitHolder = $this->getMock('OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface');
        $productUnitHolder
            ->expects(static::any())
            ->method('getEntityIdentifier')
            ->willReturn($id);
        $productUnitHolder
            ->expects(static::any())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $productUnitHolder
            ->expects(static::any())
            ->method('getProductUnitCode')
            ->willReturn($productUnitCode);
        $productUnitHolder
            ->expects(static::any())
            ->method('getProductHolder')
            ->willReturn($productHolder);

        return $productUnitHolder;
    }

    /**
     * @param int $id
     * @param string $productSku
     * @param Product $product
     * @return \PHPUnit_Framework_MockObject_MockObject|ProductHolderInterface
     */
    protected function createProductHolder($id, $productSku, Product $product = null)
    {
        /* @var $productHolder \PHPUnit_Framework_MockObject_MockObject|ProductHolderInterface */
        $productHolder = $this->getMock('OroB2B\Bundle\ProductBundle\Model\ProductHolderInterface');
        $productHolder
            ->expects(static::any())
            ->method('getId')
            ->willReturn($id);

        $productHolder
            ->expects(static::any())
            ->method('getProduct')
            ->willReturn($product);

        $productHolder
            ->expects(static::any())
            ->method('getProductSku')
            ->willReturn($productSku);

        return $productHolder;
    }
}

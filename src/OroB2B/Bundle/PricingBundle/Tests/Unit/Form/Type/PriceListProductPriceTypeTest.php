<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as StubEntityType;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListProductPriceType;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingService;

class PriceListProductPriceTypeTest extends FormIntegrationTestCase
{
    /**
     * @var PriceListProductPriceType
     */
    protected $formType;

    /**
     * @var RoundingService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $roundingService;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AbstractQuery
     */
    protected $query;

    /**
     * @var array
     */
    protected $units = [
        'item',
        'kg'
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->roundingService = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Rounding\RoundingService')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new PriceListProductPriceType($this->getRegistry(), $this->roundingService);
        $this->formType->setDataClass('OroB2B\Bundle\PricingBundle\Entity\ProductPrice');

        parent::setUp();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        unset($this->query, $this->formType);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $registry = $this->getRegistryForEntityIdentifierType();

        $entityType = new EntityType($registry);

        $productUnitSelection = new StubEntityType(
            $this->prepareProductUnitSelectionChoices(),
            ProductUnitSelectionType::NAME
        );

        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->any())
            ->method('get')
            ->with('oro_currency.allowed_currencies')
            ->will($this->returnValue(['USD', 'EUR']));

        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();

        $productSelect = new ProductSelectTypeStub();

        return [
            new PreloadedExtension(
                [
                    $entityType->getName() => $entityType,
                    ProductSelectType::NAME => $productSelect,
                    ProductUnitSelectionType::NAME => $productUnitSelection,
                    PriceType::NAME => new PriceType(),
                    CurrencySelectionType::NAME => new CurrencySelectionType($configManager, $localeSettings)
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @param ProductPrice $defaultData
     * @param array $submittedData
     * @param ProductPrice $expectedData
     * @param boolean $rounding
     * @dataProvider submitProvider
     */
    public function testSubmit(
        ProductPrice $defaultData,
        array $submittedData,
        ProductPrice $expectedData,
        $rounding = false
    ) {
        if ($rounding) {
            $this->roundingService->expects($this->once())
                ->method('round')
                ->willReturnCallback(
                    function ($value, $precision) {
                        return round($value, $precision);
                    }
                );
        }

        $this->query->expects($this->atLeastOnce())
            ->method('execute')
            ->willReturn(
                [
                    $this->getProductEntityWithPrecision(1, 'kg', 3),
                    $this->getProductEntityWithPrecision(2, 'kg', 3)
                ]
            );

        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertEquals([], $form->getErrors());
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        /** @var Product $expectedProduct */
        $expectedProduct = $this->getProductEntityWithPrecision(2, 'kg', 3);
        $expectedPrice = (new Price())->setValue(42)->setCurrency('USD');

        $expectedProductPrice = new ProductPrice();
        $expectedProductPrice
            ->setProduct($expectedProduct)
            ->setQuantity(123)
            ->setUnit($expectedProduct->getUnitPrecision('kg')->getUnit())
            ->setPrice($expectedPrice);

        $expectedProductPrice2 = clone $expectedProductPrice;
        $expectedProductPrice2->setQuantity(123.556);

        $defaultProductPrice = new ProductPrice();

        return [
            'product price without data' => [
                'defaultData'   => $defaultProductPrice,
                'submittedData' => [],
                'expectedData'  => $defaultProductPrice,
                'rounding'      => false
            ],
            'product price with data' => [
                'defaultData'   => $defaultProductPrice,
                'submittedData' => [
                    'product' => 1,
                    'quantity'  => 123,
                    'unit'      => 'kg',
                    'price'     => [
                        'value'    => 42,
                        'currency' => 'USD'
                    ]
                ],
                'expectedData' => $expectedProductPrice,
                'rounding'      => true
            ],
            'product price with data for rounding' => [
                'defaultData'   => $defaultProductPrice,
                'submittedData' => [
                    'product' => 1,
                    'quantity'  => 123.5555,
                    'unit'      => 'kg',
                    'price'     => [
                        'value'    => 42,
                        'currency' => 'USD'
                    ]
                ],
                'expectedData' => $expectedProductPrice2,
                'rounding'     => true
            ]
        ];
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(PriceListProductPriceType::NAME, $this->formType->getName());
    }

    /**
     * @return array
     */
    protected function prepareProductUnitSelectionChoices()
    {
        $choices = [];
        foreach ($this->units as $unitCode) {
            $unit = new ProductUnit();
            $unit->setCode($unitCode);
            $choices[$unitCode] = $unit;
        }

        return $choices;
    }

    /**
     * @return ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRegistryForEntityIdentifierType()
    {
        $metadata = $this->getMockBuilder('\Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('id'));
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata));
        $this->query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMockForAbstractClass();
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->any())
            ->method('getQuery')
            ->will($this->returnValue($this->query));
        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->any())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qb));
        $em->expects($this->any())
            ->method('getRepository')
            ->with($this->isType('string'))
            ->will($this->returnValue($repo));

        /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $registry */
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($em));

        return $registry;
    }

    /**
     * @return RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRegistry()
    {
        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->any())
            ->method('find')
            ->willReturn($this->getProductEntityWithPrecision(1, 'kg', 3));

        /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $registry */
        $registry = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');
        $registry->expects($this->any())
            ->method('getRepository')
            ->with($this->isType('string'))
            ->willReturn($repo);

        return $registry;
    }

    /**
     * @param string $className
     * @param int $id
     * @return object
     */
    protected function getEntity($className, $id)
    {
        $entity = new $className;
        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);

        return $entity;
    }

    /**
     * @param integer $productId
     * @param string $unitCode
     * @param integer $precision
     * @return Product
     */
    protected function getProductEntityWithPrecision($productId, $unitCode, $precision = 0)
    {
        /** @var \OroB2B\Bundle\ProductBundle\Entity\Product $product */
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', $productId);

        $unit = new ProductUnit();
        $unit->setCode($unitCode);

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision
            ->setPrecision($precision)
            ->setUnit($unit)
            ->setProduct($product);

        return $product->addUnitPrecision($unitPrecision);
    }
}

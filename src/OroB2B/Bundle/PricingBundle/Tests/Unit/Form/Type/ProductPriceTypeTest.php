<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as StubEntityType;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Form\Type\ProductPriceType;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class ProductPriceTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductPriceType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AbstractQuery
     */
    protected $query;

    /**
     * @var array
     */
    protected $priceLists = [
        'Test',
        'Test 01'
    ];

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
        $this->formType = new ProductPriceType();
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

        $priceListSelect = new PriceListSelectTypeStub();

        return [
            new PreloadedExtension(
                [
                    $entityType->getName() => $entityType,
                    PriceListSelectType::NAME => $priceListSelect,
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
     * @param $defaultData
     * @param $submittedData
     * @param $expectedData
     * @dataProvider submitProvider
     */
    public function testSubmit(
        $defaultData,
        $submittedData,
        $expectedData
    ) {
        $this->query->expects($this->atLeastOnce())
            ->method('execute')
            ->willReturn(
                [
                    $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', 1),
                    $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', 2)
                ]
            );

        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        /** @var PriceList $expectedPriceList */
        $expectedPriceList = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', 2);

        return [
            'product price without data' => [
                'defaultData'   => new ProductPrice(),
                'submittedData' => [],
                'expectedData'  => new ProductPrice()
            ],
            'product price with data' => [
                'defaultData'   => new ProductPrice(),
                'submittedData' => [
                    'priceList' => 1,
                    'quantity'  => 123,
                    'unit'      => 'kg',
                    'price'     => [
                        'value'    => 42,
                        'currency' => 'USD'
                    ]
                ],
                'expectedData' => (new ProductPrice())->setQuantity(123)
                    ->setPriceList($expectedPriceList)
                    ->setUnit((new ProductUnit())->setCode('kg'))
                    ->setPrice((new Price())->setValue(42)->setCurrency('USD'))
            ]

        ];
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(ProductPriceType::NAME, $this->formType->getName());
    }

    /**
     * @return array
     */
    protected function preparePriceListSelectionChoices()
    {
        $choices = [];
        foreach ($this->priceLists as $key => $priceListName) {
            $priceList = new PriceList();
            $priceList->setName($priceListName);
            $choices[] = $priceList;
        }

        return $choices;
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
}

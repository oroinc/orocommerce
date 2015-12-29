<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;

class PriceListSelectWithPriorityTypeTest extends FormIntegrationTestCase
{
    const PRICE_LIST_ID = 42;

    /**
     * @var PriceListSelectWithPriorityType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new PriceListSelectWithPriorityType();

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType(
            [
                self::PRICE_LIST_ID => $this->getPriceList(self::PRICE_LIST_ID)
            ]
        );

        return [
            new PreloadedExtension(
                [
                    $entityType->getName() => $entityType,
                    PriceListSelectType::NAME => new PriceListSelectTypeStub(),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @dataProvider submitDataProvider
     * @param array $defaultData
     * @param array $submittedData
     * @param array $expectedData
     */
    public function testSubmit(array $defaultData, array $submittedData, array $expectedData)
    {
        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $existingPriceList = $this->getPriceList(123);

        /** @var PriceList $expectedPriceList */
        $expectedPriceList = $this->getPriceList(self::PRICE_LIST_ID);

        return [
            'without default data' => [
                'defaultData'   => [],
                'submittedData' => [
                    'priceList' => self::PRICE_LIST_ID,
                    'priority'  => 100,
                    'merge'     => true
                ],
                'expectedData' => [
                    'priceList' => $expectedPriceList,
                    'priority'  => 100,
                    'merge'     => true
                ]
            ],
            'with default data' => [
                'defaultData'   => [
                    'priceList' => $existingPriceList,
                    'priority'  => 50,
                    'merge'     => true
                ],
                'submittedData' => [
                    'priceList' => self::PRICE_LIST_ID,
                    'priority'  => 100,
                    'merge'     => true
                ],
                'expectedData' => [
                    'priceList' => $expectedPriceList,
                    'priority'  => 100,
                    'merge'     => true
                ]
            ],
        ];
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(PriceListSelectWithPriorityType::NAME, $this->formType->getName());
    }

    /**
     * @param int $id
     * @return PriceList
     */
    protected function getPriceList($id)
    {
        $priceList = new PriceList();
        $reflectionClass = new \ReflectionClass($priceList);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($priceList, $id);

        return $priceList;
    }
}

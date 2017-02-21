<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Extension\SortableExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use Symfony\Component\Form\Forms;

class PriceListSelectWithPriorityTypeTest extends FormIntegrationTestCase
{
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

        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->getFormFactory();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType([]);

        return [
            new PreloadedExtension(
                [
                    $entityType->getName() => $entityType,
                    PriceListSelectType::NAME => new PriceListSelectTypeStub(),
                ],
                ['form' => [new SortableExtension()]]
            ),
            new ValidatorExtension(Validation::createValidator()),
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
        $options = [
            'sortable' => true,
            'sortable_property_path' => "[sort_order]",
            'allow_extra_fields' => true,
        ];

        $form = $this->factory->create($this->formType, $defaultData, $options);

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
        $existingPriceList = $this->getPriceList(PriceListSelectTypeStub::PRICE_LIST_1);

        /** @var PriceList $expectedPriceList */
        $expectedPriceList = $this->getPriceList(PriceListSelectTypeStub::PRICE_LIST_2);

        return [
            'without default data' => [
                'defaultData' => [],
                'submittedData' => [
                    'priceList' => PriceListSelectTypeStub::PRICE_LIST_2,
                    '_position' => 100,
                    'mergeAllowed' => true,
                ],
                'expectedData' => [
                    'priceList' => $expectedPriceList,
                    'sort_order' => 100,
                    'mergeAllowed' => true,
                ],
            ],
            'without default data merge off' => [
                'defaultData' => [],
                'submittedData' => [
                    'priceList' => PriceListSelectTypeStub::PRICE_LIST_2,
                    '_position' => 100,
                    'mergeAllowed' => false,
                ],
                'expectedData' => [
                    'priceList' => $expectedPriceList,
                    'sort_order' => 100,
                    'mergeAllowed' => false,
                ],
            ],
            'with default data' => [
                'defaultData' => [
                    'priceList' => $existingPriceList,
                    'mergeAllowed' => true,
                ],
                'submittedData' => [
                    'priceList' => PriceListSelectTypeStub::PRICE_LIST_2,
                    '_position' => 100,
                    'mergeAllowed' => true,
                ],
                'expectedData' => [
                    'priceList' => $expectedPriceList,
                    'sort_order' => 100,
                    'mergeAllowed' => true,
                ],
            ],
            'with default data merge off' => [
                'defaultData' => [
                    'priceList' => $existingPriceList,
                    'mergeAllowed' => false,
                ],
                'submittedData' => [
                    'priceList' => PriceListSelectTypeStub::PRICE_LIST_2,
                    '_position' => 100,
                    'mergeAllowed' => false,
                ],
                'expectedData' => [
                    'priceList' => $expectedPriceList,
                    'sort_order' => 100,
                    'mergeAllowed' => false,
                ],
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

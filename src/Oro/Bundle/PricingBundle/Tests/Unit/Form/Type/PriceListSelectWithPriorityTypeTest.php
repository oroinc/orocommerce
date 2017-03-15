<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\Forms;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use Oro\Bundle\FormBundle\Form\Extension\SortableExtension;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;

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

        parent::setUp();
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
     * @param mixed $defaultData
     * @param array $submittedData
     * @param array $expectedData
     */
    public function testSubmit($defaultData, array $submittedData, $expectedData)
    {
        $form = $this->factory->create($this->formType, $defaultData);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
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
                'defaultData' => null,
                'submittedData' => [
                    'priceList' => PriceListSelectTypeStub::PRICE_LIST_2,
                    '_position' => 100,
                    'mergeAllowed' => true,
                ],
                'expectedData' => (new BasePriceListRelation())
                    ->setSortOrder(100)
                    ->setMergeAllowed(true)
                    ->setPriceList($expectedPriceList),
            ],
            'without default data merge off' => [
                'defaultData' => null,
                'submittedData' => [
                    'priceList' => PriceListSelectTypeStub::PRICE_LIST_2,
                    '_position' => 100,
                    'mergeAllowed' => false,
                ],
                'expectedData' => (new BasePriceListRelation())
                    ->setSortOrder(100)
                    ->setMergeAllowed(false)
                    ->setPriceList($expectedPriceList),
            ],
            'with default data' => [
                'defaultData' => (new BasePriceListRelation())
                    ->setSortOrder(10)
                    ->setMergeAllowed(true)
                    ->setPriceList($existingPriceList),

                'submittedData' => [
                    'priceList' => PriceListSelectTypeStub::PRICE_LIST_2,
                    '_position' => 100,
                    'mergeAllowed' => true,
                ],

                'expectedData' => (new BasePriceListRelation())
                    ->setSortOrder(100)
                    ->setMergeAllowed(true)
                    ->setPriceList($expectedPriceList),
            ],
            'with default data merge off' => [
                'defaultData' => (new BasePriceListRelation())
                    ->setSortOrder(10)
                    ->setMergeAllowed(false)
                    ->setPriceList($existingPriceList),

                'submittedData' => [
                    'priceList' => PriceListSelectTypeStub::PRICE_LIST_2,
                    '_position' => 100,
                    'mergeAllowed' => false,
                ],
                'expectedData' => (new BasePriceListRelation())
                    ->setSortOrder(100)
                    ->setMergeAllowed(false)
                    ->setPriceList($expectedPriceList),
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

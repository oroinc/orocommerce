<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSystemConfigType;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use OroB2B\Bundle\PricingBundle\Tests\Unit\SystemConfig\ConfigsGeneratorTrait;

class PriceListSystemConfigTypeTest extends FormIntegrationTestCase
{
    use ConfigsGeneratorTrait;

    /** @var array */
    protected $testPriceLists = [];

    /** @var array */
    protected $testPriceListConfigs = [];

    /** @var PriceListSystemConfigType */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new PriceListSystemConfigType(
            'OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfig',
            'OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigBag'
        );
        $this->testPriceListConfigs = $this->createConfigs(2);
        foreach ($this->testPriceListConfigs as $config) {
            $this->testPriceLists[$config->getPriceList()->getId()] = $config->getPriceList();
        }

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType(
            $this->testPriceLists
        );
        $oroCollectionType = new CollectionType();
        $priceListWithPriorityType = new PriceListSelectWithPriorityType();

        return [
            new PreloadedExtension([
                $oroCollectionType::NAME => $oroCollectionType,
                $priceListWithPriorityType::NAME => $priceListWithPriorityType,
                PriceListSelectType::NAME => new PriceListSelectTypeStub(),
                $entityType->getName() => $entityType,
            ], []),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testSubmit()
    {
        $defaultData = [];
        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit([
            PriceListSystemConfigType::COLLECTION_FIELD_NAME => [
                [
                    'priceList' => 1,
                    'priority' => 100,
                ],
                [
                    'priceList' => 2,
                    'priority' => 200,
                ]
            ]
        ]);
        $this->assertTrue($form->isValid());

        $expected = [PriceListSystemConfigType::COLLECTION_FIELD_NAME => $this->testPriceListConfigs];

        $this->assertEquals($expected, $form->getData());
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(PriceListSystemConfigType::NAME, $this->formType->getName());
    }
}

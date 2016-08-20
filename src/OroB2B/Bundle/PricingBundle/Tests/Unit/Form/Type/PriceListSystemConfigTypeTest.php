<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSystemConfigType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use Oro\Bundle\PricingBundle\Tests\Unit\SystemConfig\ConfigsGeneratorTrait;
use Oro\Bundle\PricingBundle\Form\Type\PriceListCollectionType;

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
            'Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig',
            'Oro\Bundle\PricingBundle\SystemConfig\PriceListConfigBag'
        );
        $this->testPriceListConfigs = $this->createConfigs(2);
        foreach ($this->testPriceListConfigs as $config) {
            $this->testPriceLists[$config->getPriceList()->getId()] = $config->getPriceList()->setName('');
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
        $priceListCollectionType = new PriceListCollectionType();
        $priceListWithPriorityType = new PriceListSelectWithPriorityType();

        return [
            new PreloadedExtension([
                $oroCollectionType::NAME => $oroCollectionType,
                $priceListCollectionType::NAME => $priceListCollectionType,
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
            [
                'priceList' => 1,
                'priority' => 100,
                'mergeAllowed' => true
            ],
            [
                'priceList' => 2,
                'priority' => 200,
                'mergeAllowed' => false
            ]
        ]);
        $this->assertTrue($form->isValid());

        $this->assertEquals($this->testPriceListConfigs, $form->getData());
    }

    public function testGetName()
    {
        $this->assertEquals(PriceListSystemConfigType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(PriceListCollectionType::NAME, $this->formType->getParent());
    }
}

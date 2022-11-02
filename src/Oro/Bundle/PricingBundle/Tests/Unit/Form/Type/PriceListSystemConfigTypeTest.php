<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Extension\SortableExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\PricingBundle\Form\Extension\PriceListFormExtension;
use Oro\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSystemConfigType;
use Oro\Bundle\PricingBundle\PricingStrategy\MergePricesCombiningStrategy;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use Oro\Bundle\PricingBundle\Tests\Unit\SystemConfig\ConfigsGeneratorTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

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
    protected function setUp(): void
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
        $entityType = new EntityTypeStub(
            $this->testPriceLists
        );
        $oroCollectionType = new CollectionType();
        $priceListCollectionType = new PriceListCollectionType();
        $priceListWithPriorityType = new PriceListSelectWithPriorityType();
        $configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->any())
            ->method('get')
            ->with('oro_pricing.price_strategy')
            ->willReturn(MergePricesCombiningStrategy::NAME);
        return [
            new PreloadedExtension(
                [
                    PriceListSystemConfigType::class => $this->formType,
                    CollectionType::class => $oroCollectionType,
                    PriceListCollectionType::class => $priceListCollectionType,
                    PriceListSelectWithPriorityType::class => $priceListWithPriorityType,
                    PriceListSelectType::class => new PriceListSelectTypeStub(),
                    EntityType::class => $entityType,
                ],
                [
                    FormType::class => [new SortableExtension()],
                    PriceListSelectWithPriorityType::class => [new PriceListFormExtension($configManager)]
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function testSubmit()
    {
        $defaultData = [];
        $form = $this->factory->create(PriceListSystemConfigType::class, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit(
            [
                [
                    'priceList' => 1,
                    '_position' => 100,
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => 2,
                    '_position' => 200,
                    'mergeAllowed' => false,
                ],
            ]
        );
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($this->testPriceListConfigs, $form->getData());
    }

    public function testGetParent()
    {
        $this->assertEquals(PriceListCollectionType::class, $this->formType->getParent());
    }
}

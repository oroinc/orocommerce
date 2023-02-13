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
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use Oro\Bundle\PricingBundle\Tests\Unit\SystemConfig\ConfigsGeneratorTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

class PriceListSystemConfigTypeTest extends FormIntegrationTestCase
{
    use ConfigsGeneratorTrait;

    private PriceListSystemConfigType $formType;
    private array $testPriceLists = [];
    private array $testPriceListConfigs = [];

    protected function setUp(): void
    {
        $this->formType = new PriceListSystemConfigType(PriceListConfig::class);
        $this->testPriceListConfigs = $this->createConfigs(2);
        foreach ($this->testPriceListConfigs as $config) {
            $priceList = $config->getPriceList();
            $this->testPriceLists[$priceList->getId()] = $priceList->setName('');
        }

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('get')
            ->with('oro_pricing.price_strategy')
            ->willReturn(MergePricesCombiningStrategy::NAME);

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    new CollectionType(),
                    new PriceListCollectionType(),
                    new PriceListSelectWithPriorityType(),
                    PriceListSelectType::class => new PriceListSelectTypeStub(),
                    EntityType::class => new EntityTypeStub($this->testPriceLists),
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

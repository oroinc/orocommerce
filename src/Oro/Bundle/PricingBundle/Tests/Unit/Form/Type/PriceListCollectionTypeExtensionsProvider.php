<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Extension\SortableExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\PricingBundle\Form\Extension\PriceListFormExtension;
use Oro\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use Oro\Bundle\PricingBundle\PricingStrategy\MergePricesCombiningStrategy;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;

class PriceListCollectionTypeExtensionsProvider extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function getExtensions()
    {
        $entityType = new EntityType([]);
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
                    CollectionType::NAME => new CollectionType(),
                    PriceListSelectWithPriorityType::NAME => new PriceListSelectWithPriorityType(),
                    PriceListSelectType::NAME => new PriceListSelectTypeStub(),
                    PriceListCollectionType::NAME => new PriceListCollectionType(),
                    $entityType->getName() => $entityType,
                ],
                [
                    'form' => [new SortableExtension()],
                    PriceListSelectWithPriorityType::NAME => [new PriceListFormExtension($configManager)]
                ]
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }
}

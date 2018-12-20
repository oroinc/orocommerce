<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Extension\SortableExtension;
use Oro\Bundle\PricingBundle\Form\Extension\PriceListFormExtension;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use Oro\Bundle\PricingBundle\PricingStrategy\MergePricesCombiningStrategy;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

class PriceListCollectionTypeExtensionsProvider extends \PHPUnit\Framework\TestCase
{
    /**
     * @return array
     */
    public function getExtensions()
    {
        $entityType = new EntityTypeStub([]);
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
                    PriceListSelectType::class => new PriceListSelectTypeStub(),
                    EntityType::class => $entityType,
                ],
                [
                    FormType::class => [new SortableExtension()],
                    PriceListSelectWithPriorityType::class => [new PriceListFormExtension($configManager)]
                ]
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }
}

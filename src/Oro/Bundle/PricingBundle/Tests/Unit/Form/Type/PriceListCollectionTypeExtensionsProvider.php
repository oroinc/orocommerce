<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;

class PriceListCollectionTypeExtensionsProvider
{
    /**
     * @return array
     */
    public function getExtensions()
    {
        $entityType = new EntityType([]);

        return [
            new PreloadedExtension([
                CollectionType::NAME => new CollectionType(),
                PriceListSelectWithPriorityType::NAME => new PriceListSelectWithPriorityType(),
                PriceListSelectType::NAME => new PriceListSelectTypeStub(),
                PriceListCollectionType::NAME => new PriceListCollectionType(),
                $entityType->getName() => $entityType,
            ], []),
            new ValidatorExtension(Validation::createValidator())
        ];
    }
}

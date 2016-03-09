<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use OroB2B\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;

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

<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ShippingBundle\Entity\FreightClass;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;

class TestEntityNameResolverDataLoader implements TestEntityNameResolverDataLoaderInterface
{
    private TestEntityNameResolverDataLoaderInterface $innerDataLoader;

    public function __construct(TestEntityNameResolverDataLoaderInterface $innerDataLoader)
    {
        $this->innerDataLoader = $innerDataLoader;
    }

    #[\Override]
    public function loadEntity(
        EntityManagerInterface $em,
        ReferenceRepository $repository,
        string $entityClass
    ): array {
        if (FreightClass::class === $entityClass) {
            $freightClass = new FreightClass();
            $freightClass->setCode('TEST_FREIGHT_CLASS');
            $repository->setReference('freightClass', $freightClass);
            $em->persist($freightClass);
            $em->flush();

            return ['freightClass'];
        }

        if (LengthUnit::class === $entityClass) {
            $lengthUnit = new LengthUnit();
            $lengthUnit->setCode('TEST_LENGTH_UNIT');
            $lengthUnit->setConversionRates(['ANOTHER' => 1.5]);
            $repository->setReference('lengthUnit', $lengthUnit);
            $em->persist($lengthUnit);
            $em->flush();

            return ['lengthUnit'];
        }

        if (WeightUnit::class === $entityClass) {
            $weightUnit = new WeightUnit();
            $weightUnit->setCode('TEST_WEIGHT_UNIT');
            $weightUnit->setConversionRates(['ANOTHER' => 1.5]);
            $repository->setReference('weightUnit', $weightUnit);
            $em->persist($weightUnit);
            $em->flush();

            return ['weightUnit'];
        }

        if (ShippingMethodsConfigsRule::class === $entityClass) {
            $rule = new Rule();
            $rule->setName('Test Shipping Methods Configs Rule');
            $rule->setSortOrder(1);
            $em->persist($rule);
            $shippingMethodsConfigsRule = new ShippingMethodsConfigsRule();
            $shippingMethodsConfigsRule->setOrganization($repository->getReference('organization'));
            $shippingMethodsConfigsRule->setRule($rule);
            $shippingMethodsConfigsRule->setCurrency('USD');
            $repository->setReference('shippingMethodsConfigsRule', $shippingMethodsConfigsRule);
            $em->persist($shippingMethodsConfigsRule);
            $em->flush();

            return ['shippingMethodsConfigsRule'];
        }

        return $this->innerDataLoader->loadEntity($em, $repository, $entityClass);
    }

    #[\Override]
    public function getExpectedEntityName(
        ReferenceRepository $repository,
        string $entityClass,
        string $entityReference,
        ?string $format,
        ?string $locale
    ): string {
        if (FreightClass::class === $entityClass) {
            return 'TEST_FREIGHT_CLASS';
        }
        if (LengthUnit::class === $entityClass) {
            return 'TEST_LENGTH_UNIT';
        }
        if (WeightUnit::class === $entityClass) {
            return 'TEST_WEIGHT_UNIT';
        }
        if (ShippingMethodsConfigsRule::class === $entityClass) {
            return EntityNameProviderInterface::SHORT === $format
                ? (string)$repository->getReference($entityReference)->getId()
                : 'USD';
        }

        return $this->innerDataLoader->getExpectedEntityName(
            $repository,
            $entityClass,
            $entityReference,
            $format,
            $locale
        );
    }
}

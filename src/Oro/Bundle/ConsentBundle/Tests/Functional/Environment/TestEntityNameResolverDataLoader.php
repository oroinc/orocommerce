<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

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
        if (Consent::class === $entityClass) {
            $consent = new Consent();
            $consent->setOrganization($repository->getReference('organization'));
            $consent->addName($this->createLocalizedFallbackValue($em, 'Test Consent'));
            $consent->addName($this->createLocalizedFallbackValue(
                $em,
                'Test Consent (de_DE)',
                $repository->getReference('de_DE')
            ));
            $consent->addName($this->createLocalizedFallbackValue(
                $em,
                'Test Consent (fr_FR)',
                $repository->getReference('fr_FR')
            ));
            $repository->setReference('consent', $consent);
            $em->persist($consent);
            $em->flush();

            return ['consent'];
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
        if (Consent::class === $entityClass) {
            return 'Localization de_DE' === $locale
                ? 'Test Consent (de_DE)'
                : 'Test Consent';
        }

        return $this->innerDataLoader->getExpectedEntityName(
            $repository,
            $entityClass,
            $entityReference,
            $format,
            $locale
        );
    }

    private function createLocalizedFallbackValue(
        EntityManagerInterface $em,
        string $value,
        ?Localization $localization = null
    ): LocalizedFallbackValue {
        $lfv = new LocalizedFallbackValue();
        $lfv->setString($value);
        if (null !== $localization) {
            $lfv->setLocalization($localization);
        }
        $em->persist($lfv);

        return $lfv;
    }
}

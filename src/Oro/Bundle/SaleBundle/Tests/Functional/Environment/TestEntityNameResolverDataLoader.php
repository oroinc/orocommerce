<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Symfony\Contracts\Translation\TranslatorInterface;

class TestEntityNameResolverDataLoader implements TestEntityNameResolverDataLoaderInterface
{
    private TestEntityNameResolverDataLoaderInterface $innerDataLoader;
    private TranslatorInterface $translator;

    public function __construct(
        TestEntityNameResolverDataLoaderInterface $innerDataLoader,
        TranslatorInterface $translator
    ) {
        $this->innerDataLoader = $innerDataLoader;
        $this->translator = $translator;
    }

    #[\Override]
    public function loadEntity(
        EntityManagerInterface $em,
        ReferenceRepository $repository,
        string $entityClass
    ): array {
        if (Quote::class === $entityClass) {
            $quote = new Quote();
            $quote->setOrganization($repository->getReference('organization'));
            $quote->setOwner($repository->getReference('user'));
            $quote->setCurrency('USD');
            $quote->setPoNumber('PO1');
            $repository->setReference('quote', $quote);
            $em->persist($quote);
            $em->flush();

            return ['quote'];
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
        if (Quote::class === $entityClass) {
            return EntityNameProviderInterface::SHORT === $format
                ? (string)$repository->getReference($entityReference)->getId()
                : $this->translator->trans(
                    'oro.frontend.sale.quote.title.label',
                    ['%id%' => $repository->getReference($entityReference)->getId()],
                    null,
                    $locale && str_starts_with($locale, 'Localization ')
                        ? substr($locale, \strlen('Localization '))
                        : $locale
                );
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

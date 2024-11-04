<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

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
        if (WebCatalog::class === $entityClass) {
            $webCatalog = new WebCatalog();
            $webCatalog->setOrganization($repository->getReference('organization'));
            $webCatalog->setOwner($repository->getReference('business_unit'));
            $webCatalog->setName('Test Web Catalog');
            $webCatalog->setDescription('Test Web Catalog Description');
            $repository->setReference('webCatalog', $webCatalog);
            $em->persist($webCatalog);
            $em->flush();

            return ['webCatalog'];
        }

        if (ContentNode::class === $entityClass) {
            $webCatalog = new WebCatalog();
            $webCatalog->setOrganization($repository->getReference('organization'));
            $webCatalog->setOwner($repository->getReference('business_unit'));
            $webCatalog->setName('Test Web Catalog');
            $em->persist($webCatalog);
            $contentNode = new ContentNode();
            $contentNode->setWebCatalog($webCatalog);
            $contentNode->addTitle($this->createLocalizedFallbackValue($em, 'Test Content Node'));
            $contentNode->addTitle($this->createLocalizedFallbackValue(
                $em,
                'Test Content Node (de_DE)',
                $repository->getReference('de_DE')
            ));
            $contentNode->addTitle($this->createLocalizedFallbackValue(
                $em,
                'Test Content Node (fr_FR)',
                $repository->getReference('fr_FR')
            ));
            $repository->setReference('contentNode', $contentNode);
            $em->persist($contentNode);
            $em->flush();

            return ['contentNode'];
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
        if (WebCatalog::class === $entityClass) {
            return 'Test Web Catalog';
        }
        if (ContentNode::class === $entityClass) {
            return 'Localization de_DE' === $locale
                ? 'Test Content Node (de_DE)'
                : 'Test Content Node';
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

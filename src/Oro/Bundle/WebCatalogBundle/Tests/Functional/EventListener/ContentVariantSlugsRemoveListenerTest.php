<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\EventListener;

use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentVariantRepository;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentVariantsData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentVariantSlugsData;

class ContentVariantSlugsRemoveListenerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadContentVariantSlugsData::class]);
    }

    public function testContentVariantParentEntityDeletion()
    {
        $doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        /** @var SlugRepository $slugRepo */
        $slugRepo = $doctrineHelper->getEntityRepositoryForClass(Slug::class);
        /** @var ContentVariantRepository $contentVariantRepo */
        $contentVariantRepo = $doctrineHelper->getEntityRepositoryForClass(ContentVariant::class);

        /** @var ContentNode $rootNode */
        $rootNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT);
        /** @var ContentVariant $rootContentVariant */
        $rootContentVariant = $this->getReference(LoadContentVariantsData::CUSTOMER_VARIANT);
        $rootContentVariantId = $rootContentVariant->getId();
        $rootContentVariantSlugIds = $rootContentVariant->getSlugs()->map(function (Slug $slug) {
            return $slug->getId();
        })->toArray();

        /** @var ContentNode $subNode */
        $subNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1);
        /** @var ContentVariant $subnode2ContentVariant */
        $subnodeContentVariant = $this->getReference(LoadContentVariantsData::CONTENT_VARIANT_SUBNODE_1);
        $subnodeContentVariantId = $subnodeContentVariant->getId();
        $subnodeContentVariantSlugIds = $subnodeContentVariant->getSlugs()->map(function (Slug $slug) {
            return $slug->getId();
        })->toArray();

        $this->assertCount(1, $slugRepo->findBy(['id' => $rootContentVariantSlugIds]));
        $this->assertCount(2, $slugRepo->findBy(['id' => $subnodeContentVariantSlugIds]));

        $em = $doctrineHelper->getEntityManagerForClass(ContentNode::class);
        $em->remove($rootNode);
        $em->flush();

        $this->assertEmpty($contentVariantRepo->findBy(['id' => $rootContentVariantId]));
        $this->assertEmpty($slugRepo->findBy(['id' => $rootContentVariantSlugIds]));
        $this->assertEmpty($contentVariantRepo->findBy(['id' => $subnodeContentVariantId]));
        $this->assertEmpty($slugRepo->findBy(['id' => $subnodeContentVariantSlugIds]));
    }
}

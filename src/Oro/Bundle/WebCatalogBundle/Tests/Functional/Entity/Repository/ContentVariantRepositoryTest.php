<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\SystemPageContentVariantType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentVariantRepository;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentVariantScopes;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentVariantsData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentVariantSlugsData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;

class ContentVariantRepositoryTest extends WebTestCase
{
    private ContentVariantRepository $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadContentVariantSlugsData::class,
            LoadContentVariantScopes::class,
        ]);
        $this->repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(ContentVariant::class)
            ->getRepository(ContentVariant::class);
    }

    public function testFindVariantBySlugFound(): void
    {
        /** @var Slug $slug */
        $slug = $this->getReference(LoadSlugsData::SLUG_URL_USER);
        /** @var ContentVariant $expectedVariant */
        $expectedVariant = $this->getReference(LoadContentVariantsData::CUSTOMER_VARIANT);
        $this->assertEquals($expectedVariant, $this->repository->findVariantBySlug($slug));
    }

    public function testFindVariantBySlugNotFound(): void
    {
        /** @var Slug $slug */
        $slug = $this->getReference(LoadSlugsData::SLUG_URL_ANONYMOUS);
        $this->assertNull($this->repository->findVariantBySlug($slug));
    }

    /**
     * @dataProvider findChildrenVariantIdsDataProvider
     */
    public function testFindChildrenVariantIds(array $context, array $expected): void
    {
        array_walk($context, function (&$item) {
            $item = $this->getReference($item);
        });

        /** @var ScopeManager $scopeManager */
        $scopeManager = $this->getContainer()->get('oro_scope.scope_manager');
        $criteria = $scopeManager->getCriteria('web_content', $context);

        /** @var ContentNode $baseNode */
        $baseNode = $this->getReference(LoadContentVariantScopes::BASE_NODE);

        $expectedIds = [];
        foreach ($expected as $nodeKey => $variantKey) {
            $expectedIds[$this->getReference($nodeKey)->getId()] = $this->getReference($variantKey)->getId();
        }

        $this->assertEquals(
            $expectedIds,
            $this->repository->findChildrenVariantIds(
                $baseNode->getId(),
                $criteria,
                SystemPageContentVariantType::TYPE
            )
        );
    }

    public function findChildrenVariantIdsDataProvider(): array
    {
        return [
            'default' => [
                'scope' => [
                    'webCatalog' => LoadWebCatalogData::CATALOG_1,
                ],
                'expected' => [
                    LoadContentVariantScopes::NODE_1 => LoadContentVariantScopes::NODE_1_VARIANT_1,
                    LoadContentVariantScopes::NODE_2 => LoadContentVariantScopes::NODE_2_VARIANT_1,
                ],
            ],
            'with priority' => [
                'scope' => [
                    'webCatalog' => LoadWebCatalogData::CATALOG_1,
                    'customer' => LoadCustomers::DEFAULT_ACCOUNT_NAME,
                ],
                'expected' => [
                    LoadContentVariantScopes::NODE_1 => LoadContentVariantScopes::NODE_1_VARIANT_1,
                    LoadContentVariantScopes::NODE_2 => LoadContentVariantScopes::NODE_2_VARIANT_2,
                ],
            ],
        ];
    }

    public function testGetContentVariantsDataWhenEmpty(): void
    {
        self::assertEquals(
            [],
            $this->repository->getContentVariantsData([PHP_INT_MAX])
        );
    }

    public function testGetContentVariantsData(): void
    {
        $contentVariant1 = $this->getReference(LoadContentVariantsData::CONTENT_VARIANT_SUBNODE_1);
        $contentVariant2 = $this->getReference(LoadContentVariantsData::CONTENT_VARIANT_SUBNODE_2);

        $result = $this->repository->getContentVariantsData([$contentVariant1->getId(), $contentVariant2->getId()]);
        $result = array_column($result, null, 'id');

        self::assertContentVariantData($contentVariant1, $result[$contentVariant1->getId()]);
        self::assertContentVariantData($contentVariant2, $result[$contentVariant2->getId()]);
    }

    private static function assertContentVariantData(ContentVariant $contentVariant, array $data): void
    {
        self::assertSame($contentVariant->getId(), $data['id']);
        self::assertEquals($contentVariant->getNode()->getId(), $data['node']['id']);

        $slugsData = array_column($data['slugs'], null, 'id');
        foreach ($contentVariant->getSlugs() as $slug) {
            self::assertArrayHasKey($slug->getId(), $slugsData);
            self::assertEquals($slug->getUrl(), $slugsData[$slug->getId()]['url']);
        }
    }
}

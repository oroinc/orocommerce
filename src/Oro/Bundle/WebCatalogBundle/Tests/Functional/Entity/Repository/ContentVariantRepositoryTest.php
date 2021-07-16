<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\SystemPageContentVariantType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentVariantRepository;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentVariantScopes;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentVariantsData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentVariantSlugsData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;
use Oro\Bundle\WebCatalogBundle\Tests\Unit\Entity\Stub\ContentNode;

class ContentVariantRepositoryTest extends WebTestCase
{
    /**
     * @var ContentVariantRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(
            [
                LoadContentVariantSlugsData::class,
                LoadContentVariantScopes::class,
            ]
        );
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
        \array_walk($context, function (&$item, $key) {
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
}

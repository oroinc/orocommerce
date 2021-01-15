<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Entity\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\Repository\TextContentVariantRepository;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadTextContentVariantsData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class TextContentVariantRepositoryTest extends WebTestCase
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var TextContentVariantRepository
     */
    private $repository;

    /**
     * @var ScopeManager
     */
    private $scopeManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                LoadTextContentVariantsData::class,
            ]
        );

        $this->doctrine = $this->getContainer()->get('doctrine');
        $this->scopeManager = $this->getContainer()->get('oro_scope.scope_manager');
        $this->repository = $this->doctrine->getRepository(TextContentVariant::class);
    }

    public function testGetMatchingVariantForBlockByCriteria()
    {
        /** @var ContentBlock $contentBlock */
        $contentBlock = $this->getReference('content_block_1');
        /** @var Scope $scope1 */
        $scope1 = $this->getReference('content_variant2_scope1');
        /** @var Scope $scope2 */
        $scope2 = $this->getReference('content_variant3_scope2');

        /** @var TextContentVariant $variant2 */
        $variant2 = $this->getReference('text_content_variant2');
        /** @var TextContentVariant $variant3 */
        $variant3 = $this->getReference('text_content_variant3');

        $criteria1 = $this->scopeManager->getCriteriaByScope($scope1, 'web_content');
        $criteria2 = $this->scopeManager->getCriteriaByScope($scope2, 'web_content');

        $actualVariant = $this->repository->getMatchingVariantForBlockByCriteria($contentBlock, $criteria1);
        $this->assertInstanceOf(TextContentVariant::class, $actualVariant);
        $this->assertEquals($variant2->getId(), $actualVariant->getId());

        $actualVariant = $this->repository->getMatchingVariantForBlockByCriteria($contentBlock, $criteria2);
        $this->assertInstanceOf(TextContentVariant::class, $actualVariant);
        $this->assertEquals($variant3->getId(), $actualVariant->getId());
    }

    public function testGetMatchingVariantForBlockByEmptyCriteria()
    {
        /** @var ContentBlock $contentBlock */
        $contentBlock = $this->getReference('content_block_1');
        $criteria = $this->scopeManager->getCriteria('web_content', []);

        /** @var TextContentVariant $defaultVariant */
        $defaultVariant = $this->getReference('text_content_variant1');

        $actualVariant = $this->repository->getMatchingVariantForBlockByCriteria($contentBlock, $criteria);
        $this->assertInstanceOf(TextContentVariant::class, $actualVariant);
        $this->assertEquals($defaultVariant->getId(), $actualVariant->getId());
    }

    public function testGetMatchingVariantForBlockByCriteriaNotMatching()
    {
        /** @var ContentBlock $contentBlock */
        $contentBlock = $this->getReference('content_block_1');
        $customer = $this->getReference(LoadCustomers::CUSTOMER_LEVEL_1_DOT_3);
        $criteria = $this->scopeManager->getCriteria('web_content', ['customer' => $customer]);

        /** @var TextContentVariant $defaultVariant */
        $defaultVariant = $this->getReference('text_content_variant1');

        $actualVariant = $this->repository->getMatchingVariantForBlockByCriteria($contentBlock, $criteria);
        $this->assertInstanceOf(TextContentVariant::class, $actualVariant);
        $this->assertEquals($defaultVariant->getId(), $actualVariant->getId());
    }

    public function testGetDefaultContentVariantForContentBlock()
    {
        /** @var ContentBlock $contentBlock */
        $contentBlock = $this->getReference('content_block_1');
        /** @var TextContentVariant $expectedVariant */
        $expectedVariant = $this->getReference('text_content_variant1');

        $actualVariant = $this->repository->getDefaultContentVariantForContentBlock($contentBlock);

        $this->assertInstanceOf(TextContentVariant::class, $actualVariant);
        $this->assertEquals($expectedVariant->getId(), $actualVariant->getId());
    }
}

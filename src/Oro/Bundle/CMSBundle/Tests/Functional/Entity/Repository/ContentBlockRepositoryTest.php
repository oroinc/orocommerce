<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Entity\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\Repository\ContentBlockRepository;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadContentBlockScopesData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ContentBlockRepositoryTest extends WebTestCase
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var ContentBlockRepository
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
                LoadContentBlockScopesData::class,
            ]
        );

        $this->doctrine = $this->getContainer()->get('doctrine');
        $this->scopeManager = $this->getContainer()->get('oro_scope.scope_manager');
        $this->repository = $this->doctrine->getRepository(ContentBlock::class);
    }

    public function testGetMostSuitableScope()
    {
        $contentBlock = $this->getReference('content_block_1');
        $expectedScope = $this->getReference('content_block1_scope1');

        $criteria = $this->scopeManager->getCriteriaByScope($expectedScope, 'web_content');
        $actualScope = $this->repository->getMostSuitableScope($contentBlock, $criteria);
        $this->assertEquals($expectedScope, $actualScope);
    }

    public function testGetMostSuitableScopeNoMatchingScopes()
    {
        $contentBlock = $this->getReference('content_block_1');
        $customer = $this->getReference(LoadCustomers::CUSTOMER_LEVEL_1_DOT_3);

        $criteria = $this->scopeManager->getCriteria('web_content', ['customer' => $customer]);
        $actualScope = $this->repository->getMostSuitableScope($contentBlock, $criteria);
        $this->assertNull($actualScope);
    }

    public function testGetMostSuitableScopeNoAssignedScopes()
    {
        $contentBlock = $this->getReference('content_block_2');

        $criteria = $this->scopeManager->getCriteriaByScope(
            $this->getReference('content_block1_scope1'),
            'web_content'
        );
        $actualScope = $this->repository->getMostSuitableScope($contentBlock, $criteria);
        $this->assertNull($actualScope);
    }
}

<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Entity\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\Repository\ContentBlockRepository;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadContentBlockScopesData;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadTextContentVariantsData;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUser;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ContentBlockRepositoryTest extends WebTestCase
{
    private ManagerRegistry $doctrine;
    private ContentBlockRepository $repository;
    private ScopeManager $scopeManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                LoadContentBlockScopesData::class,
                LoadTextContentVariantsData::class,
                LoadCustomerUser::class,
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

    public function testGetContentBlockAliasById()
    {
        /** @var ContentBlock $block */
        $block = $this->getReference('content_block_1');

        /** @var CustomerUser $user */
        $user = $this->getReference(LoadCustomerUser::CUSTOMER_USER);
        $this->getContainer()
            ->get('security.token_storage')
            ->setToken(new UsernamePasswordOrganizationToken(
                $user,
                'k',
                $user->getOrganization(),
                $user->getUserRoles()
            ));

        self::assertEquals(
            $block->getAlias(),
            $this->repository->getContentBlockAliasById(
                $block->getId(),
                $this->getContainer()->get(AclHelper::class)
            )
        );

        $this->getContainer()->get('security.token_storage')->setToken(null);
    }

    public function testGetContentBlockAliasByNotExistingId()
    {
        /** @var CustomerUser $user */
        $user = $this->getReference(LoadCustomerUser::CUSTOMER_USER);
        $this->getContainer()
            ->get('security.token_storage')
            ->setToken(new UsernamePasswordOrganizationToken(
                $user,
                'k',
                $user->getOrganization(),
                $user->getUserRoles()
            ));

        self::assertNull($this->repository->getContentBlockAliasById(-1, $this->getContainer()->get(AclHelper::class)));

        $this->getContainer()->get('security.token_storage')->setToken(null);
    }
}

<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserRoleACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class EmbeddedFragmentControllerAclTest extends WebTestCase
{
    private const ROLE_CREATE_BLOCK = 'embedded_customer_user_role_create';
    private const ROLE_VIEW_BLOCK = 'embedded_customer_user_role_view';

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadCustomerUserRoleACLData::class]);
    }

    public function testDeniesBlockWhoseTargetPageIsGuardedOnlyByAnAclAttribute(): void
    {
        $this->loginUser(LoadCustomerUserRoleACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY);

        $response = $this->requestFragment(self::ROLE_CREATE_BLOCK);

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testDeniesTheSameBlocksItsTargetPageDenies(): void
    {
        $this->loginUser(LoadCustomerUserRoleACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY);

        $this->client->request('GET', $this->getUrl('oro_customer_frontend_customer_user_role_create'));
        $pageResponse = $this->client->getResponse();

        $fragmentResponse = $this->requestFragment(self::ROLE_CREATE_BLOCK);

        self::assertSame(
            $pageResponse->getStatusCode(),
            $fragmentResponse->getStatusCode(),
            'A fragment must not widen access to the page behind it.'
        );
        self::assertResponseStatusCodeEquals($fragmentResponse, Response::HTTP_FORBIDDEN);
    }

    public function testServesBlockWhoseTargetPageTheRoleAllows(): void
    {
        $this->loginUser(LoadCustomerUserRoleACLData::USER_ACCOUNT_1_ROLE_DEEP);

        /** @var CustomerUserRole $role */
        $role = $this->getReference(LoadCustomerUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL);

        $response = $this->requestFragment(self::ROLE_VIEW_BLOCK, ['id' => $role->getId()]);
        $content = self::getJsonResponseContent($response, Response::HTTP_OK);

        self::assertArrayHasKey(self::ROLE_VIEW_BLOCK, $content);
    }

    public function testDeniesBlockOfAnEntityThatBelongsToAnotherCustomer(): void
    {
        $this->loginUser(LoadCustomerUserRoleACLData::USER_ACCOUNT_2_ROLE_LOCAL);

        /** @var CustomerUserRole $role */
        $role = $this->getReference(LoadCustomerUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL);

        $response = $this->requestFragment(self::ROLE_VIEW_BLOCK, ['id' => $role->getId()]);

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    private function requestFragment(string $block, array $routeParameters = []): Response
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_storefront_embedded_fragment', ['block' => $block] + $routeParameters)
        );

        return $this->client->getResponse();
    }
}

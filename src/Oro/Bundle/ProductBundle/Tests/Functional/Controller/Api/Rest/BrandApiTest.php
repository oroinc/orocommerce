<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadBrandData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class BrandApiTest extends RestJsonApiTestCase
{
    use RolePermissionExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateWsseAuthHeader());
        $this->loadFixtures([LoadBrandData::class]);
    }

    public function testGetAction(): void
    {
        $this->updateRolePermission(
            User::ROLE_ADMINISTRATOR,
            Brand::class,
            AccessLevel::GLOBAL_LEVEL
        );

        /** @var Brand $brand */
        $brand = $this->getReference(LoadBrandData::BRAND_1);

        $id = $brand->getId();

        self::assertGreaterThan(0, $id);

        $this->get(['entity' => 'brands', 'id' => $id]);
    }

    public function testGetActionWhenBrandPermissionIsSetToNone(): void
    {
        $this->updateRolePermission(
            User::ROLE_ADMINISTRATOR,
            Brand::class,
            AccessLevel::NONE_LEVEL
        );

        /** @var Brand $brand1 */
        $brand1 = $this->getReference(LoadBrandData::BRAND_1);
        /** @var Brand $brand2 */
        $brand2 = $this->getReference(LoadBrandData::BRAND_2);

        $id1 = $brand1->getId();
        $id2 = $brand2->getId();

        $response = $this->get(
            ['entity' => 'brands', 'id' => $id1],
            assertValid: false
        );

        self::assertResponseStatusCodeEquals($response, 403);

        $response = $this->get(
            ['entity' => 'brands', 'id' => $id2],
            assertValid: false
        );

        self::assertResponseStatusCodeEquals($response, 403);
    }

    public function testGetActionWithBrandViewPermissionToBusinessUnit(): void
    {
        $this->updateRolePermission(
            User::ROLE_ADMINISTRATOR,
            Brand::class,
            AccessLevel::LOCAL_LEVEL
        );

        /** @var Brand $brand1 */
        $brand1 = $this->getReference(LoadBrandData::BRAND_1);
        /** @var Brand $brand2 */
        $brand2 = $this->getReference(LoadBrandData::BRAND_2);

        $id1 = $brand1->getId();
        $id2 = $brand2->getId();

        $this->get(['entity' => 'brands', 'id' => $id1]);

        $response = $this->get(
            ['entity' => 'brands', 'id' => $id2],
            assertValid: false
        );

        self::assertResponseStatusCodeEquals($response, 403);
    }

    public function testGetListAction(): void
    {
        $this->updateRolePermission(
            User::ROLE_ADMINISTRATOR,
            Brand::class,
            AccessLevel::GLOBAL_LEVEL
        );

        $response = $this->cget(['entity' => 'brands']);
        $content = self::jsonToArray($response->getContent());
        $data = $content['data'] ?? [];

        self::assertCount(2, $data);
    }

    public function testGetListActionWithBrandViewPermissionToBusinessUnit(): void
    {
        $this->updateRolePermission(
            User::ROLE_ADMINISTRATOR,
            Brand::class,
            AccessLevel::LOCAL_LEVEL
        );

        /** @var Brand $brand1 */
        $brand1 = $this->getReference(LoadBrandData::BRAND_1);

        $response = $this->cget(['entity' => 'brands']);
        $content = self::jsonToArray($response->getContent());
        $data = $content['data'] ?? [];

        self::assertCount(1, $data);
        self::assertArrayContains(['type' => 'brands', 'id' => (string)$brand1->getId()], current($data));
    }

    public function testPatchAction(): void
    {
        $this->updateRolePermissions(
            User::ROLE_ADMINISTRATOR,
            Brand::class,
            [
                'VIEW'   => AccessLevel::GLOBAL_LEVEL,
                'DELETE' => AccessLevel::GLOBAL_LEVEL,
                'EDIT' => AccessLevel::GLOBAL_LEVEL
            ]
        );

        $brand2 = $this->getReference(LoadBrandData::BRAND_2);
        $this->patch(
            ['entity' => 'brands', 'id' => (string)$brand2->getId()],
            [
                'data' => [
                    'id' => (string)$brand2->getId(),
                    'type' => 'brands', 'attributes' => ['status' => 'disabled']
                ]
            ]
        );
    }

    public function testPatchActionWithBrandEditPermissionToBusinessUnit(): void
    {
        $this->updateRolePermissions(
            User::ROLE_ADMINISTRATOR,
            Brand::class,
            [
                'VIEW'   => AccessLevel::GLOBAL_LEVEL,
                'DELETE' => AccessLevel::GLOBAL_LEVEL,
                'EDIT' => AccessLevel::LOCAL_LEVEL
            ]
        );

        $brand2 = $this->getReference(LoadBrandData::BRAND_2);
        $response = $this->patch(
            ['entity' => 'brands', 'id' => (string)$brand2->getId()],
            [
                'data' => [
                    'id' => (string)$brand2->getId(),
                    'type' => 'brands', 'attributes' => ['status' => 'disabled']
                ]
            ],
            assertValid: false
        );
        self::assertResponseStatusCodeEquals($response, 403);
    }

    public function testDeleteAction(): void
    {
        $this->updateRolePermissions(
            User::ROLE_ADMINISTRATOR,
            Brand::class,
            [
                'VIEW'   => AccessLevel::GLOBAL_LEVEL,
                'DELETE' => AccessLevel::GLOBAL_LEVEL,
            ]
        );

        $brand1 = $this->getReference(LoadBrandData::BRAND_1);
        $brand2 = $this->getReference(LoadBrandData::BRAND_2);

        $id1 = $brand1->getId();
        $id2 = $brand2->getId();

        self::assertGreaterThan(0, $id1);
        self::assertGreaterThan(0, $id2);

        $this->get(['entity' => 'brands', 'id' => $id1]);
        $this->get(['entity' => 'brands', 'id' => $id2]);

        $this->delete(['entity' => 'brands', 'id' => $id1]);

        $this->get(['entity' => 'brands', 'id' => $id2]);
        $response = $this->get(['entity' => 'brands', 'id' => $id1], assertValid: false);
        self::assertResponseStatusCodeEquals($response, 404);
    }

    public function testDeleteActionWithBrandDeletePermissionToBusinessUnit(): void
    {
        $this->updateRolePermissions(
            User::ROLE_ADMINISTRATOR,
            Brand::class,
            [
                'VIEW'   => AccessLevel::GLOBAL_LEVEL,
                'DELETE' => AccessLevel::LOCAL_LEVEL,
            ]
        );

        $brand2 = $this->getReference(LoadBrandData::BRAND_2);

        $id2 = $brand2->getId();

        self::assertGreaterThan(0, $id2);

        $this->get(['entity' => 'brands', 'id' => $id2]);

        $response = $this->delete(['entity' => 'brands', 'id' => $id2], assertValid: false);
        self::assertResponseStatusCodeEquals($response, 403);
    }
}

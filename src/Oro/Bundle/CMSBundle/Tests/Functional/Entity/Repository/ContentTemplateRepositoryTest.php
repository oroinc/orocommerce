<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CMSBundle\Entity\ContentTemplate;
use Oro\Bundle\CMSBundle\Entity\Repository\ContentTemplateRepository;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadContentTemplateData;
use Oro\Bundle\SecurityBundle\Test\Functional\AclAwareTestTrait;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @dbIsolationPerTest
 */
class ContentTemplateRepositoryTest extends WebTestCase
{
    use AclAwareTestTrait;

    private ContentTemplateRepository $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(
            [
                LoadContentTemplateData::class,
                LoadUserData::class,
            ]
        );

        $doctrine = self::getContainer()->get('doctrine');
        $this->repository = $doctrine->getRepository(ContentTemplate::class);
    }

    public function testFindContentTemplatesByTags(): void
    {
        $tag1 = $this->getReference(LoadContentTemplateData::CONTENT_TEMPLATE_TAG_1);
        $tag2 = $this->getReference(LoadContentTemplateData::CONTENT_TEMPLATE_TAG_2);
        $tag4 = $this->getReference(LoadContentTemplateData::CONTENT_TEMPLATE_TAG_4);
        $expectedResults = [
            [
                'template' => $this->getReference(LoadContentTemplateData::CONTENT_TEMPLATE_1),
                'tags' => [$tag1->getName(), $tag2->getName()],
            ],
            [
                'template' => $this->getReference(LoadContentTemplateData::CONTENT_TEMPLATE_2),
                'tags' => [],
            ],
            [
                'template' => $this->getReference(LoadContentTemplateData::CONTENT_TEMPLATE_4),
                'tags' => [$tag1->getName(), $tag4->getName()],
            ],
        ];

        self::assertEquals($expectedResults, $this->repository->findContentTemplatesByTags());
    }

    public function testFindContentTemplatesByTagsWhenSpecifiedTags(): void
    {
        $tag1 = $this->getReference(LoadContentTemplateData::CONTENT_TEMPLATE_TAG_1);

        $expectedResults = [
            [
                'template' => $this->getReference(LoadContentTemplateData::CONTENT_TEMPLATE_1),
                'tags' => [$tag1->getName()],
            ],
            [
                'template' => $this->getReference(LoadContentTemplateData::CONTENT_TEMPLATE_4),
                'tags' => [$tag1->getName()],
            ],
        ];

        self::assertEquals(
            $expectedResults,
            $this->repository->findContentTemplatesByTags([$tag1])
        );
    }

    public function testFindContentTemplatesByTagsWhenNoPermissionsOnTags(): void
    {
        self::updateRolePermissions(LoadRolesData::ROLE_USER, [
            'entity:' . ContentTemplate::class => ['VIEW_SYSTEM'],
            'entity:' . Tag::class => [],
        ]);

        $user = $this->getReference(LoadUserData::SIMPLE_USER);
        $this->updateUserSecurityToken($user->getEmail());

        $expectedResults = [
            [
                'template' => $this->getReference(LoadContentTemplateData::CONTENT_TEMPLATE_1),
                'tags' => [],
            ],
            [
                'template' => $this->getReference(LoadContentTemplateData::CONTENT_TEMPLATE_2),
                'tags' => [],
            ],
            [
                'template' => $this->getReference(LoadContentTemplateData::CONTENT_TEMPLATE_4),
                'tags' => [],
            ],
        ];

        self::assertEquals($expectedResults, $this->repository->findContentTemplatesByTags());
    }

    public function testFindContentTagsWhenViewBasicPermission(): void
    {
        self::updateRolePermissions(LoadRolesData::ROLE_USER, [
            'entity:' . ContentTemplate::class => ['VIEW_BASIC'],
        ]);

        $user = $this->getReference(LoadUserData::SIMPLE_USER);
        $this->updateUserSecurityToken($user->getEmail());

        self::assertEquals(
            [
                [
                    'template' => $this->getReference(LoadContentTemplateData::CONTENT_TEMPLATE_4),
                    'tags' => [
                        $this->getReference(LoadContentTemplateData::CONTENT_TEMPLATE_TAG_1)->getName(),
                        $this->getReference(LoadContentTemplateData::CONTENT_TEMPLATE_TAG_4)->getName(),
                    ],
                ],
            ],
            $this->repository->findContentTemplatesByTags()
        );
    }

    public function testFindContentTagsWhenViewBasicPermissionOnTags(): void
    {
        self::updateRolePermissions(LoadRolesData::ROLE_USER, [
            'entity:' . ContentTemplate::class => ['VIEW_BASIC'],
            'entity:' . Tag::class => ['VIEW_BASIC'],
        ]);

        $user = $this->getReference(LoadUserData::SIMPLE_USER);
        $this->updateUserSecurityToken($user->getEmail());

        self::assertEquals(
            [
                [
                    'template' => $this->getReference(LoadContentTemplateData::CONTENT_TEMPLATE_4),
                    'tags' => [$this->getReference(LoadContentTemplateData::CONTENT_TEMPLATE_TAG_4)->getName()],
                ],
            ],
            $this->repository->findContentTemplatesByTags()
        );
    }

    public function testFindContentTagsWhenNoPermissions(): void
    {
        self::updateRolePermissions(LoadRolesData::ROLE_USER, [
            'entity:' . ContentTemplate::class => [],
            'entity:' . Tag::class => [],
        ]);

        $user = $this->getReference(LoadUserData::SIMPLE_USER);
        $this->updateUserSecurityToken($user->getEmail());

        self::assertEquals([], $this->repository->findContentTemplatesByTags());
    }
}

<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity;

use Oro\Bundle\CMSBundle\Entity\ContentTemplate;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ContentTemplateTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /**
     * @dataProvider contentTemplateDataProvider
     */
    public function testAccessors(ContentTemplate $template, array $fieldsArray): void
    {
        self::assertEquals($template->getName(), $fieldsArray['name']);
        self::assertEquals($template->getContent(), $fieldsArray['content']);
        self::assertEquals($template->getOwner(), $fieldsArray['user']);
        self::assertEquals($template->getOrganization(), $fieldsArray['organization']);
        self::assertEquals($template->getCreatedAt(), $fieldsArray['createdAt']);
        self::assertEquals($template->getUpdatedAt(), $fieldsArray['updatedAt']);
        self::assertEquals($template->isEnabled(), $fieldsArray['enabled']);
        self::assertTrue($template->isEnabled());
        self::assertNull($template->getId());
    }

    private function contentTemplateDataProvider(): array
    {
        $contentTemplate = new ContentTemplate();
        $name = 'test_name';
        $content = 'test_content';
        $user = new User();
        $organization = new Organization();
        $createdAt = new \DateTime();
        $updatedAt = new \DateTime();
        $enabled = true;

        $contentTemplate->setName($name);
        $contentTemplate->setContent($content);
        $contentTemplate->setOwner($user);
        $contentTemplate->setOrganization($organization);
        $contentTemplate->setCreatedAt($createdAt);
        $contentTemplate->setUpdatedAt($updatedAt);
        $contentTemplate->setEnabled($enabled);

        return [
            [
                $contentTemplate,
                [
                    'name'  => $name,
                    'content' => $content,
                    'user' => $user,
                    'organization' => $organization,
                    'createdAt' => $createdAt,
                    'updatedAt' => $updatedAt,
                    'enabled' => $enabled
                ]
            ]
        ];
    }
}

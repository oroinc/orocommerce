<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class PlaceholderTest extends WebTestCase
{
    use RolePermissionExtension;

    protected function setUp(): void
    {
        $this->initClient([], static::generateBasicAuthHeader());

        $this->ensureSessionIsAvailable();

        $this->updateUserSecurityToken(self::AUTH_USER);
    }

    public function testVisibilityOfCatalogSidebar()
    {
        self::assertNotEmpty($this->getCatalogSidebarActions());

        $this->updateRolePermission(
            User::ROLE_ADMINISTRATOR,
            Category::class,
            AccessLevel::NONE_LEVEL
        );

        self::assertEmpty($this->getCatalogSidebarActions());
    }

    private function getCatalogSidebarActions(): array
    {
        $placeholderItems = self::getContainer()->get('oro_ui.placeholder.provider')->getPlaceholderItems(
            'product_index_sidebar',
            []
        );

        $actionItems = \array_filter(\array_column($placeholderItems, 'action'));
        return \array_filter(
            $actionItems,
            static function (string $actionItem) {
                return $actionItem === 'Oro\Bundle\CatalogBundle\Controller\ProductController::sidebarAction';
            }
        );
    }
}

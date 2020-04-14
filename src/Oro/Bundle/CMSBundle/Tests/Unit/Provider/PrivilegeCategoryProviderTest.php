<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Oro\Bundle\CMSBundle\Provider\PrivilegeCategoryProvider;
use Oro\Bundle\UserBundle\Model\PrivilegeCategory;

class PrivilegeCategoryProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PrivilegeCategoryProvider */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->provider = new PrivilegeCategoryProvider();
    }

    public function testGetName(): void
    {
        $this->assertEquals('cms', $this->provider->getName());
    }

    public function testGetRolePrivilegeCategory(): void
    {
        $this->assertEquals(
            new PrivilegeCategory('cms', 'oro.cms.privilege.category.cms.label', true, 6),
            $this->provider->getRolePrivilegeCategory()
        );
    }
}

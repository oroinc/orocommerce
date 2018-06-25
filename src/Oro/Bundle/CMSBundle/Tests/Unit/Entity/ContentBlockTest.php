<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity;

use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\ContentBlock;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ContentBlockTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new ContentBlock(), [
            ['id', 1],
            ['alias', 'test_alias'],
            ['owner', new BusinessUnit()],
            ['organization', new Organization()],
            ['enabled', true],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ]);

        $this->assertPropertyCollections(new ContentBlock(), [
            ['titles', new LocalizedFallbackValue()],
            ['scopes', new Scope()],
            ['contentVariants', new TextContentVariant()],
        ]);
    }

    public function testIsUpdatedAtSet()
    {
        $entity = new ContentBlock();
        $entity->setUpdatedAt(new \DateTime());

        $this->assertTrue($entity->isUpdatedAtSet());
    }

    public function testIsUpdatedAtNotSet()
    {
        $entity = new ContentBlock();
        $entity->setUpdatedAt(null);

        $this->assertFalse($entity->isUpdatedAtSet());
    }

    public function testResetScopes()
    {
        $scope = new Scope();
        $contentNode = new ContentBlock();
        $contentNode->addScope($scope);

        $this->assertNotEmpty($contentNode->getScopes());

        $contentNode->resetScopes();

        $this->assertEmpty($contentNode->getScopes());
    }

    public function testIsEnabled()
    {
        $entity = new ContentBlock();

        $this->assertTrue($entity->isEnabled());

        $entity->setEnabled(false);

        $this->assertFalse($entity->isEnabled());
    }
}

<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class SearchTermTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['id', 42],
            ['actionType', 'redirect'],
            ['redirectActionType', 'redirect'],
            ['modifyActionType', 'original_results'],
            ['redirectUri', 'https://example.com'],
            ['redirectSystemPage', 'sample_route'],
            ['phrases', 'phrase1,phrase2,phrase3'],
            ['redirect301', true],
            ['partialMatch', true],
            ['owner', new BusinessUnit()],
            ['organization', new Organization()],
            ['createdAt', new \DateTime('now', new \DateTimeZone('UTC'))],
            ['updatedAt', new \DateTime('now', new \DateTimeZone('UTC'))],
        ];

        self::assertPropertyAccessors(new SearchTerm(), $properties);
    }

    public function testCollections(): void
    {
        $collections = [
            ['scopes', new Scope()],
        ];

        self::assertPropertyCollections(new SearchTerm(), $collections);
    }

    public function testSetScopes(): void
    {
        $scope = new Scope();
        $searchTerm = new SearchTerm();
        $searchTerm->setScopes(new ArrayCollection([$scope]));

        self::assertCount(1, $searchTerm->getScopes());

        $searchTerm->setScopes(new ArrayCollection());

        self::assertEmpty($searchTerm->getScopes());
    }
}

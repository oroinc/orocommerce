<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Entity;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class SlugTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 1],
            ['url', '/test/page'],
            ['slugPrototype', 'page'],
            ['routeName', 'oro_cms_page_view'],
            ['routeParameters', ['id' => 1]],
            ['localization', new Localization()],
        ];

        $this->assertPropertyAccessors(new Slug(), $properties);

        $this->assertPropertyCollections(new Slug(), [
            ['redirects', new Redirect()],
            ['scopes', new Scope()]
        ]);
    }

    public function testToString()
    {
        $url = '/test';
        $slug = new Slug();
        $slug->setUrl($url);
        $this->assertEquals($url, (string)$slug);
    }

    public function testRouteParametersConsistency()
    {
        $routeParameters = [
            'id' => '20',
            'type' => 'normal',
            'x' => '2.3'
        ];
        $slug = new Slug();
        $slug->setRouteParameters($routeParameters);
        $expected = [
            'id' => 20,
            'type' => 'normal',
            'x' => 2.3
        ];
        $this->assertSame($expected, $slug->getRouteParameters());
    }

    public function testFillScopesHash()
    {
        $slug = new Slug();
        $localization = $this->getEntity(Localization::class, ['id' => 42]);
        $slug->setLocalization($localization);
        self::assertEquals(md5(':42'), $slug->getScopesHash());
        $scope = $this->getEntity(Scope::class, ['id' => 425]);
        $slug->addScope($scope);
        $scope2 = $this->getEntity(Scope::class, ['id' => 420]);
        $slug->addScope($scope2);
        self::assertEquals(md5('420,425:42'), $slug->getScopesHash());
    }
}

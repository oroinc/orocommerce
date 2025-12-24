<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Helper\SlugScopeHelper;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class SlugScopeHelperTest extends TestCase
{
    use EntityTrait;

    /** @dataProvider getScopesHashDataProvider */
    public function testGetScopesHash(
        ArrayCollection $scopes,
        ?Localization $localization,
        string $expectedResultString
    ): void {
        self::assertEquals(md5($expectedResultString), SlugScopeHelper::getScopesHash($scopes, $localization));
    }

    public function getScopesHashDataProvider(): array
    {
        $localization = $this->getEntity(Localization::class, ['id' => 1]);
        $scopeOne = $this->getEntity(Scope::class, ['id' => 1]);
        $scopeTwo = $this->getEntity(Scope::class, ['id' => 2]);
        $scopeThree = $this->getEntity(Scope::class, ['id' => 3]);

        return [
            'empty scopes, no localization' => [
                'scopes' => new ArrayCollection(),
                'localization' => null,
                'expectedResultString' => ':'
            ],
            'empty scopes, with localization' => [
                'scopes' => new ArrayCollection(),
                'localization' => $localization,
                'expectedResultString' => ':1'
            ],
            'one scope, no localization' => [
                'scopes' => new ArrayCollection([$scopeOne]),
                'localization' => null,
                'expectedResultString' => '1:'
            ],
            'two scopes, no localization' => [
                'scopes' => new ArrayCollection([$scopeOne, $scopeTwo]),
                'localization' => null,
                'expectedResultString' => '1,2:'
            ],
            'one scope, with localization' => [
                'scopes' => new ArrayCollection([$scopeOne]),
                'localization' => $localization,
                'expectedResultString' => '1:1'
            ],
            'two scopes, with localization' => [
                'scopes' => new ArrayCollection([$scopeOne, $scopeTwo]),
                'localization' => $localization,
                'expectedResultString' => '1,2:1'
            ],
            'three unsorted scopes, with localization' => [
                'scopes' => new ArrayCollection([$scopeTwo, $scopeThree, $scopeOne]),
                'localization' => $localization,
                'expectedResultString' => '1,2,3:1'
            ],
        ];
    }
}

<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Entity;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class ContentVariantTest extends TestCase
{
    use EntityTrait;
    use EntityTestCaseTrait;

    public function testAccessors(): void
    {
        $this->assertPropertyAccessors(new ContentVariant(), [
            ['type', 'productPage'],
            ['systemPageRoute', 'some_route'],
            ['node', new ContentNode()],
            ['default', true],
            ['overrideVariantConfiguration', true],
            ['doNotRenderTitle', true],
            ['expanded', true],
        ]);

        $this->assertPropertyCollections(new ContentVariant(), [
            ['scopes', new Scope()],
            ['slugs', new Slug()]
        ]);
    }

    public function testGetLocalizedSlug(): void
    {
        $variant = new ContentVariant();
        $defaultSlug = new Slug();
        $localizedSlug = new Slug();
        $localization = new Localization();
        $localizedSlug->setLocalization($localization);

        $variant->addSlug($defaultSlug)->addSlug($localizedSlug);

        $this->assertEquals($defaultSlug, $variant->getBaseSlug());
        $this->assertEquals($localizedSlug, $variant->getSlugByLocalization($localization));
    }
}

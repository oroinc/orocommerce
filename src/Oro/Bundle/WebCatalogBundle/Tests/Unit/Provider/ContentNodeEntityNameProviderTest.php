<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\WebCatalogBundle\Provider\ContentNodeEntityNameProvider;
use Oro\Bundle\WebCatalogBundle\Tests\Unit\Entity\Stub\ContentNode;
use Oro\Component\Testing\ReflectionUtil;

class ContentNodeEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    private ContentNodeEntityNameProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new ContentNodeEntityNameProvider();
    }

    private function getContentNodeTitle(string $string, ?Localization $localization = null): LocalizedFallbackValue
    {
        $value = new LocalizedFallbackValue();
        $value->setString($string);
        $value->setLocalization($localization);

        return $value;
    }

    private function getLocalization(string $code): Localization
    {
        $language = new Language();
        $language->setCode($code);

        $localization = new Localization();
        ReflectionUtil::setId($localization, 123);
        $localization->setLanguage($language);

        return $localization;
    }

    public function testGetNameForUnsupportedEntity(): void
    {
        self::assertFalse(
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', new \stdClass())
        );
    }

    public function testGetName(): void
    {
        $node = new ContentNode();
        $node->addTitle($this->getContentNodeTitle('default title'));
        $node->addTitle($this->getContentNodeTitle('localized title', $this->getLocalization('en')));

        self::assertEquals(
            'default title',
            $this->provider->getName(EntityNameProviderInterface::FULL, null, $node)
        );
    }

    public function testGetNameForLocalization(): void
    {
        $node = new ContentNode();
        $node->addTitle($this->getContentNodeTitle('default title'));
        $node->addTitle($this->getContentNodeTitle('localized title', $this->getLocalization('en')));

        self::assertEquals(
            'localized title',
            $this->provider->getName(EntityNameProviderInterface::FULL, $this->getLocalization('en'), $node)
        );
    }

    public function testGetNameDQLForUnsupportedEntity(): void
    {
        self::assertFalse(
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, null, \stdClass::class, 'node')
        );
    }

    public function testGetNameDQL(): void
    {
        self::assertEquals(
            'CAST((SELECT COALESCE(node_t.string, node_t.text)'
            . ' FROM Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue node_t'
            . ' WHERE node_t MEMBER OF node.titles AND node_t.localization IS NULL) AS string)',
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, null, ContentNode::class, 'node')
        );
    }

    public function testGetNameDQLForLocalization(): void
    {
        self::assertEquals(
            'CAST((SELECT COALESCE(node_t.string, node_t.text, node_dt.string, node_dt.text)'
            . ' FROM Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue node_dt'
            . ' LEFT JOIN Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue node_t'
            . ' WITH node_t MEMBER OF node.titles AND node_t.localization = 123'
            . ' WHERE node_dt MEMBER OF node.titles AND node_dt.localization IS NULL) AS string)',
            $this->provider->getNameDQL(
                EntityNameProviderInterface::FULL,
                $this->getLocalization('en'),
                ContentNode::class,
                'node'
            )
        );
    }
}

<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Provider;

use Oro\Bundle\ConsentBundle\Provider\ConsentEntityNameProvider;
use Oro\Bundle\ConsentBundle\Tests\Unit\Stub\Consent;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Component\Testing\ReflectionUtil;

class ConsentEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    private ConsentEntityNameProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new ConsentEntityNameProvider();
    }

    private function getConsentName(string $string, ?Localization $localization = null): LocalizedFallbackValue
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
        $consent = new Consent();
        $consent->addName($this->getConsentName('default name'));
        $consent->addName($this->getConsentName('localized name', $this->getLocalization('en')));

        self::assertEquals(
            'default name',
            $this->provider->getName(EntityNameProviderInterface::FULL, null, $consent)
        );
    }

    public function testGetNameForLocalization(): void
    {
        $consent = new Consent();
        $consent->addName($this->getConsentName('default name'));
        $consent->addName($this->getConsentName('localized name', $this->getLocalization('en')));

        self::assertEquals(
            'localized name',
            $this->provider->getName(EntityNameProviderInterface::FULL, $this->getLocalization('en'), $consent)
        );
    }

    public function testGetNameDQLForUnsupportedEntity(): void
    {
        self::assertFalse(
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, 'en', \stdClass::class, 'entity')
        );
    }

    public function testGetNameDQL(): void
    {
        self::assertEquals(
            'CAST((SELECT COALESCE(consent_n.string, consent_n.text)'
            . ' FROM Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue consent_n'
            . ' WHERE consent_n MEMBER OF consent.names AND consent_n.localization IS NULL) AS string)',
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, null, Consent::class, 'consent')
        );
    }

    public function testGetNameDQLForLocalization(): void
    {
        self::assertEquals(
            'CAST((SELECT COALESCE(consent_n.string, consent_n.text, consent_dn.string, consent_dn.text)'
            . ' FROM Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue consent_dn'
            . ' LEFT JOIN Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue consent_n'
            . ' WITH consent_n MEMBER OF consent.names AND consent_n.localization = 123'
            . ' WHERE consent_dn MEMBER OF consent.names AND consent_dn.localization IS NULL) AS string)',
            $this->provider->getNameDQL(
                EntityNameProviderInterface::FULL,
                $this->getLocalization('en'),
                Consent::class,
                'consent'
            )
        );
    }
}

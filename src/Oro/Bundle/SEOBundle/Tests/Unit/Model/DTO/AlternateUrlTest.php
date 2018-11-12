<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Model\DTO;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\SEOBundle\Model\DTO\AlternateUrl;
use Oro\Bundle\TranslationBundle\Entity\Language;

class AlternateUrlTest extends \PHPUnit\Framework\TestCase
{
    const URL = 'http://someurl.com/';

    public function testCreateWithoutLocalization()
    {
        $alternateUrl = new AlternateUrl(self::URL);

        $this->assertEquals(self::URL, $alternateUrl->getUrl());
        $this->assertEquals('x-default', $alternateUrl->getLanguageCode());
    }

    public function testCreateWithLocalization()
    {
        $localization = (new Localization())->setLanguage((new Language())->setCode('en_CA'));
        $alternateUrl = new AlternateUrl(self::URL, $localization);

        $this->assertEquals(self::URL, $alternateUrl->getUrl());
        $this->assertEquals('en-ca', $alternateUrl->getLanguageCode());
    }
}

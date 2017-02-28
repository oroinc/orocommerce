<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Model\DTO;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\SEOBundle\Model\DTO\AlternateUrl;

class AlternateUrlTest extends \PHPUnit_Framework_TestCase
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
        $localization = (new Localization())->setLanguageCode('en_CA');
        $alternateUrl = new AlternateUrl(self::URL, $localization);

        $this->assertEquals(self::URL, $alternateUrl->getUrl());
        $this->assertEquals('en-ca', $alternateUrl->getLanguageCode());
    }
}

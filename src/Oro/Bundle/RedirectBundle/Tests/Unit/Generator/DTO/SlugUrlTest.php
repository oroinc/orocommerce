<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Generator\DTO;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Generator\DTO\SlugUrl;

class SlugUrlTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor()
    {
        $localization = new Localization();

        $obj = new SlugUrl('test', $localization);
        $this->assertEquals('test', $obj->getUrl());
        $this->assertEquals($localization, $obj->getLocalization());
    }

    public function testSetUrl()
    {
        $expected = 'another';
        $slugUrl = new SlugUrl('test');
        $slugUrl->setUrl($expected);

        $this->assertEquals($expected, $slugUrl->getUrl());
    }
}

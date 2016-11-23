<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Generator\DTO;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\WebCatalogBundle\Generator\DTO\SlugUrl;

class SlugUrlTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $localization = new Localization();

        $obj = new SlugUrl('test', $localization);
        $this->assertEquals('test', $obj->getUrl());
        $this->assertEquals($localization, $obj->getLocalization());
    }
}

<?php

namespace OroB2B\Bundle\WebsiteConfigBundle\Tests\Unit\Placeholder;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteConfigBundle\Placeholder\PlaceholderFilter;

class PlaceholderFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testIsWebsitePageTrue()
    {
        $placeholderFilter = new PlaceholderFilter();
        $this->assertTrue($placeholderFilter->isWebsitePage($this->getMock(Website::class)));
    }

    public function testIsWebsitePageFalse()
    {
        $placeholderFilter = new PlaceholderFilter();
        $this->assertFalse($placeholderFilter->isWebsitePage(new \stdClass()));
    }
}

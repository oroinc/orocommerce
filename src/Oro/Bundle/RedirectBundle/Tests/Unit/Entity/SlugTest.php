<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;
use Oro\Bundle\RedirectBundle\Entity\Slug;

class SlugTest extends EntityTestCase
{
    public function testProperties()
    {
        $properties = [
            ['id', 1],
            ['url', 'test/page'],
            ['routeName', 'oro_cms_page_view'],
            ['routeParameters', ['id' => 1]],
        ];

        $this->assertPropertyAccessors(new Slug(), $properties);
    }

    /**
     * @param $fullUrl
     * @param $slugUrl
     * @dataProvider getSlugUrlDataProvider
     */
    public function testGetSlugUrl($fullUrl, $slugUrl)
    {
        $slug = new Slug();
        $slug->setUrl($fullUrl);
        $this->assertEquals($slugUrl, $slug->getSlugUrl());
    }

    /**
     * @return array
     */
    public function getSlugUrlDataProvider()
    {
        return [
            'no slash' => [
                'fullUrl' => 'first',
                'slugUrl' => 'first',
            ],
            'one level' =>  [
                'fullUrl' => '/first',
                'slugUrl' => 'first',
            ],
            'two levels' => [
                'fullUrl' => '/first/second',
                'slugUrl' => 'second',
            ],
        ];
    }

    public function testToString()
    {
        $url = '/test';
        $slug = new Slug();
        $slug->setUrl($url);
        $this->assertEquals($url, (string)$slug);
    }
}

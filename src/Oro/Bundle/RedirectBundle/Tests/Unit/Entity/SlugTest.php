<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Entity;

use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class SlugTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    
    public function testProperties()
    {
        $properties = [
            ['id', 1],
            ['url', 'test/page'],
            ['routeName', 'oro_cms_page_view'],
            ['routeParameters', ['id' => 1]],
        ];

        $this->assertPropertyAccessors(new Slug(), $properties);

        $this->assertPropertyCollections(new Slug(), [
            ['redirects', new Redirect()],
        ]);
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
        $this->assertAttributeEquals(md5($slug->getUrl()), 'urlHash', $slug);
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

<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Cache;

use Oro\Bundle\RedirectBundle\Cache\UrlDataStorage;

class UrlDataStorageTest extends \PHPUnit\Framework\TestCase
{
    public function testGetUrlForNotExistentUrl()
    {
        $storage = new UrlDataStorage();
        $this->assertEmpty($storage->getUrl(['someParameter' => 'someValue']));
    }

    public function testGetUrlForAddedUrlWithoutSlug()
    {
        $storage = new UrlDataStorage();
        $parameters = ['someParameter' => 'someValue'];
        $storage->setUrl($parameters, 'some_url');
        $this->assertEquals('some_url', $storage->getUrl($parameters));
        $this->assertNull($storage->getSlug($parameters));
    }

    public function testGetUrlForAddedUrlWithSlug()
    {
        $storage = new UrlDataStorage();
        $parameters = ['someParameter' => 'someValue'];
        $storage->setUrl($parameters, '/category/slug', 'slug');
        $this->assertEquals('/category/slug', $storage->getUrl($parameters));
        $this->assertEquals('slug', $storage->getSlug($parameters));
    }

    public function testVarExportWithUrlAndScope()
    {
        $storage = new UrlDataStorage();
        $storage->setUrl(['someParameter' => 'someValue'], '/test/some_url', 'some_url');
        $storage->setUrl(['someParameter' => 'someValue'], '/test/some_url-en', 'some_url-en', 3);

        $export = <<<EOT
\Oro\Bundle\RedirectBundle\Cache\UrlDataStorage::__set_state(array(
   'data' => 
  array (
    'ae49506996071bccf2163b287491f8c2' => 
    array (
      0 => 
      array (
        'p' => '/test',
        's' => 'some_url',
      ),
      3 => 
      array (
        'p' => '/test',
        's' => 'some_url-en',
      ),
    ),
  ),
))
EOT;
        $this->assertEquals($export, var_export($storage, true));
    }

    public function testGetUrlKey()
    {
        $routeParameters = ['route_parameter' => 'parameter_value'];

        $this->assertEquals(
            md5(serialize($routeParameters)),
            UrlDataStorage::getUrlKey($routeParameters)
        );
    }

    public function testGettersAndSetters()
    {
        $parameters = ['id' => 1];
        $url = '/prefix/test';
        $slug = 'test';
        $localizationId = 42;

        $storage = new UrlDataStorage();
        $storage->setUrl($parameters, $url, $slug);
        $storage->setUrl($parameters, $url, $slug, $localizationId);
        $this->assertEquals($url, $storage->getUrl($parameters));
        $this->assertEquals($slug, $storage->getSlug($parameters));
        $this->assertEquals($url, $storage->getUrl($parameters, $localizationId));
        $this->assertEquals($slug, $storage->getSlug($parameters, $localizationId));

        $this->assertEquals(
            [
                '2f35704147e4489fb9b8aeb31dbabaef' => [
                    0 => ['p' => '/prefix', 's' => $slug],
                    $localizationId => ['p' => '/prefix', 's' => $slug]
                ]
            ],
            $storage->getData()
        );

        $storage->removeUrl($parameters);
        $this->assertArrayHasKey('2f35704147e4489fb9b8aeb31dbabaef', $storage->getData());
        $this->assertFalse($storage->getUrl($parameters));
        $this->assertFalse($storage->getSlug($parameters));
        $this->assertEquals($url, $storage->getUrl($parameters, $localizationId));
        $this->assertEquals($slug, $storage->getSlug($parameters, $localizationId));

        $storage->removeUrl($parameters, $localizationId);
        $this->assertArrayNotHasKey('2f35704147e4489fb9b8aeb31dbabaef', $storage->getData());
        $this->assertFalse($storage->getUrl($parameters));
        $this->assertFalse($storage->getSlug($parameters));
        $this->assertFalse($storage->getUrl($parameters, $localizationId));
        $this->assertFalse($storage->getSlug($parameters, $localizationId));
    }

    public function testSetState()
    {
        $url = '/test';
        $slug = 'test';
        $data = [
            'data' => [
                '2f35704147e4489fb9b8aeb31dbabaef' => [
                    0 => ['u' => $url, 's' => $slug],
                    3 => ['u' => $url, 's' => $slug],
                ]
            ]
        ];

        $storage = UrlDataStorage::__set_state($data);
        $this->assertEquals($data['data'], $storage->getData());
    }

    public function testSetStateWithDataNotArray()
    {
        $storage = UrlDataStorage::__set_state(['data' => 3]);
        $this->assertEquals([], $storage->getData());
    }

    public function testSetStateWithIncorrectData()
    {
        $storage = UrlDataStorage::__set_state(42);
        $this->assertEquals([], $storage->getData());
    }

    public function testMerge()
    {
        $localizationId = 3;
        $addedLocalizationId = 5;
        $parameters = ['id' => 1];
        $url = '/test';
        $localizedUrl = '/test-en';
        $newUrl = '/test-new';
        $localizedSlug = 'test-en';
        $slug = 'test';
        $newSlug = 'test-new';

        $storage = new UrlDataStorage();
        $storage->setUrl($parameters, $url, $slug, null);
        $storage->setUrl($parameters, $localizedUrl, $localizedSlug, $localizationId);
        $this->assertEquals($url, $storage->getUrl($parameters));
        $this->assertEquals($slug, $storage->getSlug($parameters));
        $this->assertEquals($localizedUrl, $storage->getUrl($parameters, $localizationId));
        $this->assertEquals($localizedSlug, $storage->getSlug($parameters, $localizationId));

        $newStorage = new UrlDataStorage();
        $newStorage->setUrl($parameters, $newUrl, $newSlug, $localizationId);
        $newStorage->setUrl($parameters, $url, $slug, $addedLocalizationId);
        $storage->merge($newStorage);

        $this->assertEquals($url, $storage->getUrl($parameters));
        $this->assertEquals($slug, $storage->getSlug($parameters));
        $this->assertEquals($newUrl, $storage->getUrl($parameters, $localizationId));
        $this->assertEquals($newSlug, $storage->getSlug($parameters, $localizationId));
        $this->assertEquals($url, $storage->getUrl($parameters, $addedLocalizationId));
        $this->assertEquals($slug, $storage->getSlug($parameters, $addedLocalizationId));
    }
}

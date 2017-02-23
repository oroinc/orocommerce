<?php

namespace Oro\Bundle\RedirectBundle\Tests\Cache;

use Oro\Bundle\RedirectBundle\Cache\UrlDataStorage;

class UrlDataStorageTest extends \PHPUnit_Framework_TestCase
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
        $storage->setUrl($parameters, 'some_url', 'slug');
        $this->assertEquals('some_url', $storage->getUrl($parameters));
        $this->assertEquals('slug', $storage->getSlug($parameters));
    }

    public function testVarExportWithUrlAndScope()
    {
        $storage = new UrlDataStorage();
        $storage->setUrl(['someParameter' => 'someValue'], '/test/some_url', 'some_url');

        $export = <<<EOT
Oro\Bundle\RedirectBundle\Cache\UrlDataStorage::__set_state(array(
   'data' => 
  array (
    'ae49506996071bccf2163b287491f8c2' => 
    array (
      'u' => '/test/some_url',
      's' => 'some_url',
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
        $url = '/test';
        $slug = 'test';

        $storage = new UrlDataStorage();
        $storage->setUrl($parameters, $url, $slug);
        $this->assertEquals($url, $storage->getUrl($parameters));
        $this->assertEquals($slug, $storage->getSlug($parameters));

        $this->assertEquals(['2f35704147e4489fb9b8aeb31dbabaef' => ['u' => $url, 's' => $slug]], $storage->getData());

        $storage->removeUrl($parameters);
        $this->assertNull($storage->getUrl($parameters));
        $this->assertNull($storage->getSlug($parameters));
    }

    public function testSetState()
    {
        $url = '/test';
        $slug = 'test';
        $data = ['data' => ['2f35704147e4489fb9b8aeb31dbabaef' => ['u' => $url, 's' => $slug]]];

        $storage = UrlDataStorage::__set_state($data);
        $this->assertEquals(['2f35704147e4489fb9b8aeb31dbabaef' => ['u' => $url, 's' => $slug]], $storage->getData());
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
        $parameters = ['id' => 1];
        $url = '/test';
        $newUrl = '/test-new';
        $slug = 'test';
        $newSlug = 'test-new';

        $storage = new UrlDataStorage();
        $storage->setUrl($parameters, $url, $slug);
        $this->assertEquals($url, $storage->getUrl($parameters));
        $this->assertEquals($slug, $storage->getSlug($parameters));

        $newStorage = new UrlDataStorage();
        $newStorage->setUrl($parameters, $newUrl, $newSlug);

        $this->assertEquals($newUrl, $newStorage->getUrl($parameters));
        $this->assertEquals($newSlug, $newStorage->getSlug($parameters));
    }
}

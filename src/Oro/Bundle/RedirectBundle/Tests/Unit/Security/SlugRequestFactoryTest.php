<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Security;

use Oro\Bundle\RedirectBundle\Security\SlugRequestFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SlugRequestFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var SlugRequestFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new SlugRequestFactory();
    }

    public function testCreateSlugRequest()
    {
        $resolvedSlugUrl = '/resolved/slug';
        $session = $this->createMock(SessionInterface::class);

        $request = Request::create('/slug', 'GET', [], [], [], [], 'test_content');
        $request->attributes->set('_resolved_slug_url', $resolvedSlugUrl);
        $request->attributes->set('attr1', 'attr_val1');
        $request->query->set('query_prm1', 'query_prm_val1');
        $request->cookies->set('cookie1', 'cookie_val1');
        $request->request->set('request_prm1', 'request_prm_val1');
        $request->files->set('file1', $this->createMock(UploadedFile::class));
        $request->setSession($session);
        $request->setLocale('en_GB');
        $request->setDefaultLocale('en');

        $slugRequest = $this->factory->createSlugRequest($request);

        self::assertNotSame($request, $slugRequest);
        self::assertEquals($resolvedSlugUrl . '?query_prm1=query_prm_val1', $slugRequest->getRequestUri());
        self::assertEquals($request->getMethod(), $slugRequest->getMethod());
        self::assertEquals($request->query->all(), $slugRequest->query->all());
        self::assertEquals($request->cookies->all(), $slugRequest->cookies->all());
        self::assertEquals($request->files->all(), $slugRequest->files->all());
        $expectedServerParameters = $request->server->all();
        $expectedServerParameters['REQUEST_URI'] = $resolvedSlugUrl . '?query_prm1=query_prm_val1';
        $expectedServerParameters['QUERY_STRING'] = 'query_prm1=query_prm_val1';
        self::assertEquals($expectedServerParameters, $slugRequest->server->all());
        self::assertEquals($request->getContent(), $slugRequest->getContent());
        self::assertSame($session, $slugRequest->hasSession() ? $slugRequest->getSession() : null);
        self::assertEquals($request->getLocale(), $slugRequest->getLocale());
        self::assertEquals($request->getDefaultLocale(), $slugRequest->getDefaultLocale());
    }

    public function testCreateSlugRequestWithoutSession()
    {
        $request = Request::create('/slug');
        $request->attributes->set('_resolved_slug_url', '/resolved/slug');

        $slugRequest = $this->factory->createSlugRequest($request);

        self::assertNotSame($request, $slugRequest);
        self::assertFalse($slugRequest->hasSession());
    }

    public function testUpdateMainRequest()
    {
        $session = $this->createMock(SessionInterface::class);

        $request = Request::create('/slug');
        $request->attributes->set('_resolved_slug_url', '/resolved/slug');
        $request->setSession($session);

        $slugRequest = Request::create(
            $request->attributes->get('_resolved_slug_url'),
            $request->getMethod(),
            $request->query->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            $request->getContent()
        );
        $slugRequest->setSession($request->getSession());
        $slugRequest->setLocale($request->getLocale());
        $slugRequest->setDefaultLocale($request->getDefaultLocale());

        $this->factory->updateMainRequest($request, $slugRequest);
    }
}

<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Security;

use Oro\Bundle\RedirectBundle\Security\RememberMeSlugRequestFactory;
use Oro\Bundle\RedirectBundle\Security\SlugRequestFactoryInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface;

class RememberMeSlugRequestFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var SlugRequestFactoryInterface */
    private $innerFactory;

    /** @var RememberMeSlugRequestFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->innerFactory = $this->createMock(SlugRequestFactoryInterface::class);

        $this->factory = new RememberMeSlugRequestFactory($this->innerFactory);
    }

    public function testCreateSlugRequestWithoutRememberMeCookieAttribute()
    {
        $request = Request::create('/slug');
        $createdSlugRequest = Request::create('/resolved/slug');

        $this->innerFactory->expects(self::once())
            ->method('createSlugRequest')
            ->with(self::identicalTo($request))
            ->willReturn($createdSlugRequest);

        $slugRequest = $this->factory->createSlugRequest($request);
        self::assertSame($createdSlugRequest, $slugRequest);
        self::assertFalse($slugRequest->attributes->has(RememberMeServicesInterface::COOKIE_ATTR_NAME));
        self::assertCount(0, $slugRequest->cookies->all());
    }

    public function testCreateSlugRequestWithRememberMeCookieAttribute()
    {
        $rememberMeCookie = Cookie::create('TESTRM', 'test_value');
        $request = Request::create('/slug');
        $request->attributes->set(RememberMeServicesInterface::COOKIE_ATTR_NAME, $rememberMeCookie);
        $createdSlugRequest = Request::create('/resolved/slug');

        $this->innerFactory->expects(self::once())
            ->method('createSlugRequest')
            ->with(self::identicalTo($request))
            ->willReturn($createdSlugRequest);

        $slugRequest = $this->factory->createSlugRequest($request);
        self::assertSame($createdSlugRequest, $slugRequest);
        self::assertSame(
            $rememberMeCookie,
            $slugRequest->attributes->get(RememberMeServicesInterface::COOKIE_ATTR_NAME)
        );
        self::assertEquals(
            $rememberMeCookie->getValue(),
            $slugRequest->cookies->get($rememberMeCookie->getName())
        );
    }

    public function testUpdateMainRequestWithoutRememberMeCookieAttribute()
    {
        $request = Request::create('/slug');
        $slugRequest = Request::create('/resolved/slug');

        $this->innerFactory->expects(self::once())
            ->method('updateMainRequest')
            ->with(self::identicalTo($request), self::identicalTo($slugRequest));

        $this->factory->updateMainRequest($request, $slugRequest);
        self::assertFalse($slugRequest->attributes->has(RememberMeServicesInterface::COOKIE_ATTR_NAME));
        self::assertCount(0, $slugRequest->cookies->all());
    }

    public function testUpdateMainRequestWithRememberMeCookieAttribute()
    {
        $rememberMeCookie = Cookie::create('TESTRM', 'test_value');
        $request = Request::create('/slug');
        $slugRequest = Request::create('/resolved/slug');
        $slugRequest->attributes->set(RememberMeServicesInterface::COOKIE_ATTR_NAME, $rememberMeCookie);

        $this->innerFactory->expects(self::once())
            ->method('updateMainRequest')
            ->with(self::identicalTo($request), self::identicalTo($slugRequest));

        $this->factory->updateMainRequest($request, $slugRequest);
        self::assertSame(
            $rememberMeCookie,
            $request->attributes->get(RememberMeServicesInterface::COOKIE_ATTR_NAME)
        );
        self::assertEquals(
            $rememberMeCookie->getValue(),
            $request->cookies->get($rememberMeCookie->getName())
        );
    }
}

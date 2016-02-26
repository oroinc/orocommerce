<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Layout\DataProvider;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Layout\LayoutContext;

use OroB2B\Bundle\AccountBundle\Layout\DataProvider\SignInProvider;

/**
 * @dbIsolation
 */
class SignInProviderTest extends WebTestCase
{
    /** @var LayoutContext */
    protected $context;

    /** @var SignInProvider */
    protected $dataProvider;

    /** @var  RequestStack */
    protected $requestStack;

    /** @var  CsrfTokenManagerInterface */
    protected $tokenManager;

    protected function setUp()
    {
        $this->initClient();

        $this->context = new LayoutContext();
        $this->requestStack = $this->getContainer()->get('request_stack');
        $this->tokenManager = $this->getContainer()->get('security.csrf.token_manager');
        $this->dataProvider = $this->getContainer()->get('orob2b_account.provider.sign_in');
    }

    public function testGetData()
    {
        $lastUsername = 'Last Username';
        $errorMessage = 'Test Error';

        $request = new Request();
        $request->setDefaultLocale('test');
        $request->attributes->set('test', 'test_test');

        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $session->set(Security::LAST_USERNAME, $lastUsername);
        $session->set(Security::AUTHENTICATION_ERROR, new AuthenticationException($errorMessage));

        $this->requestStack->push($request);

        $actual = $this->dataProvider->getData($this->context);

        $this->assertInternalType('array', $actual);

        $this->assertArrayHasKey('csrf_token', $actual);
        $this->assertNotEmpty($actual['csrf_token']);

        $this->assertArrayHasKey('last_username', $actual);
        $this->assertEquals($lastUsername, $actual['last_username']);

        $this->assertArrayHasKey('error', $actual);
        $this->assertEquals($errorMessage, $actual['error']);
    }
}

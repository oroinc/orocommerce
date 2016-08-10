<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Testing\Unit\EntityTrait;
use OroB2B\Bundle\AccountBundle\Layout\DataProvider\AccountUserAddressProvider;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AccountUserAddressProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $router;

    /**
     * @var FragmentHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fragmentHandler;

    /** @var AccountUserAddressProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->router = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');
        $this->fragmentHandler = $this->getMockBuilder('Symfony\Component\HttpKernel\Fragment\FragmentHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new AccountUserAddressProvider($this->router, $this->fragmentHandler);
    }

    public function testGetAddressCreateAclResource()
    {
        $result = 'orob2b_account_frontend_account_user_address_create';

        $this->assertSame($result, $this->provider->getAddressCreateAclResource());
    }

    public function testGetAddressUpdateAclResource()
    {
        $result = 'orob2b_account_frontend_account_user_address_update';

        $this->assertSame($result, $this->provider->getAddressUpdateAclResource());
    }

    public function testGetComponentOptions()
    {
        $entity = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUser', ['id' => 40]);

        $this->router->expects($this->exactly(2))
            ->method('generate')
            ->willReturnMap([
                [
                    'orob2b_api_account_frontend_get_accountuser_addresses',
                    ['entityId' => $entity->getId()],
                    UrlGeneratorInterface::ABSOLUTE_PATH,
                    '/address/list/test/url'
                ],
                [
                    'orob2b_account_frontend_account_user_address_create',
                    ['entityId' => $entity->getId()],
                    UrlGeneratorInterface::ABSOLUTE_PATH,
                    '/address/create/test/url'
                ]
            ]);

        $this->fragmentHandler->expects($this->once())
            ->method('render')
            ->with('/address/list/test/url')
            ->willReturn(['data']);

        $data = $this->provider->getComponentOptions($entity);

        $this->assertEquals(
            [
                'entityId' => 40,
                'addressListUrl' => '/address/list/test/url',
                'addressCreateUrl' => '/address/create/test/url',
                'addressUpdateRouteName' => 'orob2b_account_frontend_account_user_address_update',
                'currentAddresses' => ['data'],
            ],
            $data
        );
    }
}

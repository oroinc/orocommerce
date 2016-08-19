<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Layout\DataProvider\AddressProvider;

class AddressProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    /** @var FragmentHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $fragmentHandler;

    /** @var AddressProvider */
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

        $this->provider = new AddressProvider($this->router, $this->fragmentHandler);
    }

    public function testGetComponentOptions()
    {
        $this->provider->setEntityClass('OroB2B\Bundle\AccountBundle\Entity\Account');
        $this->provider->setListRouteName('orob2b_api_account_frontend_get_account_addresses');
        $this->provider->setCreateRouteName('orob2b_account_frontend_account_address_create');
        $this->provider->setUpdateRouteName('orob2b_account_frontend_account_address_update');

        /** @var Account $entity */
        $entity = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => 40]);

        $this->router->expects($this->exactly(2))
            ->method('generate')
            ->willReturnMap([
                [
                    'orob2b_api_account_frontend_get_account_addresses',
                    ['entityId' => $entity->getId()],
                    UrlGeneratorInterface::ABSOLUTE_PATH,
                    '/address/list/test/url'
                ],
                [
                    'orob2b_account_frontend_account_address_create',
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
                'addressUpdateRouteName' => 'orob2b_account_frontend_account_address_update',
                'currentAddresses' => ['data'],
            ],
            $data
        );
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testGetComponentOptionsWithoutRouteName()
    {
        /** @var Account $entity */
        $entity = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account');

        $this->provider->setListRouteName('');
        $this->provider->getComponentOptions($entity);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testGetComponentOptionsWithWrongEntityClass()
    {
        /** @var Account $entity */
        $entity = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account');

        $this->provider->setEntityClass('OroB2B\Bundle\AccountBundle\Entity\AccountUser');
        $this->provider->getComponentOptions($entity);
    }
}

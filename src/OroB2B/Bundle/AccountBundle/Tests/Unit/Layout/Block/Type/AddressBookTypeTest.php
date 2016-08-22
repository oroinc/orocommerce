<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\Options;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;
use Oro\Component\Layout\Tests\Unit\BaseBlockTypeTestCase;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Layout\Block\Type\AddressBookType;

class AddressBookTypeTest extends BaseBlockTypeTestCase
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

    /**
     * {@inheritdoc}
     */
    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);

        $this->router = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');
        $this->fragmentHandler = $this->getMockBuilder('Symfony\Component\HttpKernel\Fragment\FragmentHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $layoutFactoryBuilder->addType(new AddressBookType($this->router, $this->fragmentHandler));
    }

    /**
     * @dataProvider optionsDataProvider
     *
     * @param array $options
     * @param array $expected
     */
    public function testSetDefaultOptions($options, $expected)
    {
        $resolvedOptions = $this->resolveOptions(AddressBookType::NAME, $options);
        $this->assertEquals($expected, $resolvedOptions);
    }

    /**
     * @return array
     */
    public function optionsDataProvider()
    {
        $entity = new AccountUser();

        return [
            'required options' => [
                'options' => [
                    'entity' => $entity,
                    'addressListRouteName' => 'address_list_test_route_name',
                    'addressCreateRouteName' => 'address_create_test_route_name',
                    'addressUpdateRouteName' => 'address_update_test_route_name'
                ],
                'expected' => [
                    'entity' => $entity,
                    'addressListRouteName' => 'address_list_test_route_name',
                    'addressUpdateRouteName' => 'address_update_test_route_name',
                    'addressCreateRouteName' => 'address_create_test_route_name',
                    'addressCreateAclResource' => null,
                    'addressUpdateAclResource' => null,
                    'useFormDialog' => false
                ]
            ],
            'all options' => [
                'options' => [
                    'entity' => $entity,
                    'addressListRouteName' => 'address_list_test_route_name',
                    'addressUpdateRouteName' => 'address_update_test_route_name',
                    'addressCreateRouteName' => 'address_create_test_route_name',
                    'addressCreateAclResource' => 'address_create_test_acl',
                    'addressUpdateAclResource' => 'address_update_test_acl',
                    'useFormDialog' => true
                ],
                'expected' => [
                    'entity' => $entity,
                    'addressListRouteName' => 'address_list_test_route_name',
                    'addressUpdateRouteName' => 'address_update_test_route_name',
                    'addressCreateRouteName' => 'address_create_test_route_name',
                    'addressCreateAclResource' => 'address_create_test_acl',
                    'addressUpdateAclResource' => 'address_update_test_acl',
                    'useFormDialog' => true
                ]
            ],
        ];
    }

    public function testGetName()
    {
        $type = $this->getBlockType(AddressBookType::NAME);

        $this->assertSame(AddressBookType::NAME, $type->getName());
    }

    public function testFinishViewException()
    {
        $this->setExpectedException(
            '\RuntimeException',
            'Expected instance of type "OroB2B\Bundle\AccountBundle\Entity\AccountUser", "stdClass" given'
        );

        $type = $this->getBlockType(AddressBookType::NAME);

        $rootView = new BlockView();
        $view = new BlockView($rootView);

        $type->finishView(
            $view,
            $this->getMock('Oro\Component\Layout\BlockInterface'),
            new Options(['entity' => new \stdClass()])
        );
    }

    public function testFinishView()
    {
        $type = $this->getBlockType(AddressBookType::NAME);

        $rootView = new BlockView();
        $view = new BlockView($rootView);

        $entity = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUser', ['id' => 42]);
        $options = new Options([
            'entity' => $entity,
            'addressListRouteName' => 'address_list_test_route_name',
            'addressCreateRouteName' => 'address_create_test_route_name',
            'addressUpdateRouteName' => 'address_update_test_route_name',
            'addressCreateAclResource' => 'address_create_test_acl',
            'addressUpdateAclResource' => 'address_update_test_acl',
            'useFormDialog' => true
        ]);

        $this->router->expects($this->exactly(2))
            ->method('generate')
            ->willReturnMap([
                [
                    'address_list_test_route_name',
                    ['entityId' => 42],
                    UrlGeneratorInterface::ABSOLUTE_PATH,
                    '/address/list/test/url'
                ],
                [
                    'address_create_test_route_name',
                    ['entityId' => 42],
                    UrlGeneratorInterface::ABSOLUTE_PATH,
                    '/address/create/test/url'
                ]
            ]);

        $this->fragmentHandler->expects($this->once())
            ->method('render')
            ->with('/address/list/test/url')
            ->willReturn(['data']);

        $type->finishView($view, $this->getMock('Oro\Component\Layout\BlockInterface'), $options);

        $this->assertEquals($entity, $view->vars['item']);
        $this->assertEquals($options['addressCreateAclResource'], $view->vars['addressCreateAclResource']);
        $this->assertEquals($options['addressUpdateAclResource'], $view->vars['addressUpdateAclResource']);
        $this->assertEquals(
            [
                'entityId' => 42,
                'addressListUrl' => '/address/list/test/url',
                'addressCreateUrl' => '/address/create/test/url',
                'addressUpdateRouteName' => 'address_update_test_route_name',
                'currentAddresses' => ['data'],
                'useFormDialog' => true,
            ],
            $view->vars['componentOptions']
        );
    }
}

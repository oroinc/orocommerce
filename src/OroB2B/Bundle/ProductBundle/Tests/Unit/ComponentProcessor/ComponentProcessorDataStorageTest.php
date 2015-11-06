<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Model;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorDataStorage;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorFilter;

class ComponentProcessorDataStorageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UrlGeneratorInterface
     */
    protected $router;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductDataStorage
     */
    protected $storage;

    /**
     * @var ComponentProcessorDataStorage
     */
    protected $processor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    private $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ComponentProcessorFilter
     */
    protected $componentProcessorFilter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Session
     */
    protected $session;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    protected function setUp()
    {
        $this->router = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');
        $this->storage = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->componentProcessorFilter = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorFilter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->processor = new ComponentProcessorDataStorage(
            $this->router,
            $this->storage,
            $this->securityFacade,
            $this->componentProcessorFilter,
            $this->session,
            $this->translator
        );
    }

    protected function tearDown()
    {
        unset($this->router, $this->storage, $this->processor);
    }

    public function testProcessWithoutRedirectRoute()
    {
        $data = ['data' => ['param' => 42]];

        $this->router->expects($this->never())
            ->method($this->anything());

        $this->storage->expects($this->once())
            ->method('set')
            ->with($data);

        $this->assertNull($this->processor->process($data, new Request()));
    }

    public function testProcessWithRedirectRoute()
    {
        $data = ['data' => ['param' => 42]];
        $redirectRouteName = 'redirect_route';
        $redirectUrl = '/redirect/url';
        $expectedResponseContent = (new RedirectResponse($redirectUrl))->getContent();

        $this->router->expects($this->once())
            ->method('generate')
            ->with(
                $redirectRouteName,
                [ProductDataStorage::STORAGE_KEY => true],
                UrlGeneratorInterface::ABSOLUTE_PATH
            )
            ->willReturn($redirectUrl);

        $this->storage->expects($this->once())
            ->method('set')
            ->with($data);

        $this->processor->setRedirectRouteName($redirectRouteName);

        $this->assertEquals(
            $expectedResponseContent,
            $this->processor->process($data, new Request())->getContent()
        );
    }

    public function testProcessorName()
    {
        $name = 'test_name';

        $this->assertNull($this->processor->getName());

        $this->processor->setName($name);

        $this->assertEquals($name, $this->processor->getName());
    }

    /**
     * @param string $acl
     * @param bool $isGranted
     * @param bool $hasLoggedUser
     * @param bool $expected
     * @dataProvider processorIsAllowedProvider
     */
    public function testProcessorIsAllowed($acl, $isGranted, $hasLoggedUser, $expected)
    {
        if (null !== $acl) {
            $this->securityFacade->expects($this->any())
                ->method('hasLoggedUser')
                ->willReturn($hasLoggedUser);
            $this->securityFacade->expects($this->any())->method('isGranted')
                ->with($acl)->willReturn($isGranted);
        }

        $this->processor->setAcl($acl);
        $this->assertEquals($expected, $this->processor->isAllowed());
    }

    /**
     * @return array
     */
    public function processorIsAllowedProvider()
    {
        return [
            [null, true, false, true],
            ['fail', false, false, false],
            ['fail', true, false, false],
            ['fail', false, true, false],
            ['success', true, true, true],
        ];
    }

    public function testValidationRequired()
    {
        $this->assertTrue($this->processor->isValidationRequired());

        $this->processor->setValidationRequired(false);

        $this->assertFalse($this->processor->isValidationRequired());
    }

    /**
     * @dataProvider processorWithScopeDataProvider
     * @param string $scope
     * @param array $data
     * @param array $restrictedData
     */
    public function testProcessorWithScope($scope, array $data, array $restrictedData)
    {
        $this->componentProcessorFilter->expects($this->once())
            ->method('filterData')
            ->with($data, ['scope' => $scope])
            ->willReturn($restrictedData);

        $this->storage->expects($this->once())
            ->method('set')
            ->with($restrictedData);

        $this->processor->setScope($scope);

        if ($data !== $restrictedData) {
            $this->translator->expects($this->any())
                ->method('trans')
                ->with('orob2b.product.frontend.quick_add.messages.not_added_products');

            $flashBag = $this->getMock('Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface');
            $flashBag->expects($this->once())
                ->method('add')
                ->with('warning');

            $this->session->expects($this->once())
                ->method('getFlashBag')
                ->willReturn($flashBag);
        }

        $this->assertNull($this->processor->process($data, new Request()));
    }

    /**
     * @return array
     */
    public function processorWithScopeDataProvider()
    {
        return [
            'restricted' => [
                'scope' => 'test',
                'data' => [
                    'entity_items_data' => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku03'],
                    ],
                ],
                'restrictedData' => [
                    'entity_items_data' => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                    ],
                ],
            ],
            'not restricted' => [
                'scope' => 'test',
                'data' => [
                    'entity_items_data' => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku03'],
                    ],
                ],
                'restrictedData' => [
                    'entity_items_data' => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku03'],
                    ],
                ],
            ],
        ];
    }
}

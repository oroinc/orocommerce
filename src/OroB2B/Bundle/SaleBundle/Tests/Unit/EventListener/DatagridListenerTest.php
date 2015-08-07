<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\EventListener;

use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\SaleBundle\EventListener\DatagridListener;

/**
 * @dbIsolation
 */
class DatagridListenerTest extends WebTestCase
{
    /**
     * @var DatagridListener
     */
    protected $listener;

    /**
     * @var string
     */
    protected $quoteClass = 'OroB2B\Bundle\SaleBundle\Entity\Quote';

    /**
     * @var string
     */
    protected $accountUserClass = 'OroB2B\Bundle\AccountBundle\Entity\AccountUser';

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $datagrid;

    /**
     * @var DatagridConfiguration|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $datagridConfig;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $this->datagridConfig = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->listener = new DatagridListener(
            $this->quoteClass,
            $this->accountUserClass,
            $this->securityFacade
        );
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     * @dataProvider buildBeforeFrontendQuotesProvider
     */
    public function testBuildBeforeFrontendQuotes(array $inputData, array $expectedData)
    {
        $this->securityFacade->expects($this->once())
            ->method('isGrantedClassPermission')
            ->with($inputData['permission']['permission'], $inputData['permission']['class'])
            ->willReturn($inputData['permission']['return'])
        ;

        $this->securityFacade->expects($inputData['mask']['expects'])
            ->method('isGrantedClassMask')
            ->with($inputData['mask']['mask'], $inputData['mask']['class'])
            ->willReturn($inputData['mask']['return'])
        ;

        $datagridConfig = DatagridConfiguration::create($inputData['config']);

        $event = new BuildBefore($this->datagrid, $datagridConfig);

        $this->listener->onBuildBeforeFrontendQuotes($event);

        $this->assertEquals($expectedData['config'], $datagridConfig->toArray());
    }

    /**
     * @return array
     */
    public function buildBeforeFrontendQuotesProvider()
    {
        $config = [
            'columns' => [
                'accountUserName' => true,
            ],
            'sorters' => [
                'columns' => [
                    'accountUserName' => true,
                ],
            ],
            'filters' => [
                'columns' => [
                    'accountUserName' => true,
                ],
            ],
        ];

        $emptyConfig = [
            'columns' => [],
            'sorters' => [
                'columns' => [],
            ],
            'filters' => [
                'columns' => [],
            ],
        ];

        return [
            'no permission VIEW for AccountUser class' => [
                'input' => [
                    'permission' => [
                        'permission'    => BasicPermissionMap::PERMISSION_VIEW,
                        'class'         => $this->accountUserClass,
                        'return'        => false,
                    ],
                    'mask' => [
                        'expects' => $this->never(),
                        'mask'    => null,
                        'class'   => null,
                        'return'  => null,
                    ],
                    'config' => $config,
                ],
                'expected' => [
                    'config' => $emptyConfig,
                ],
            ],
            'no permission VIEW_LOCAL for Quote class' => [
                'input' => [
                    'permission' => [
                        'permission'    => BasicPermissionMap::PERMISSION_VIEW,
                        'class'         => $this->accountUserClass,
                        'return'        => true,
                    ],
                    'mask' => [
                        'expects' => $this->once(),
                        'mask'    => EntityMaskBuilder::MASK_VIEW_LOCAL,
                        'class'   => $this->quoteClass,
                        'return'  => false,
                    ],
                    'config' => $config,
                ],
                'expected' => [
                    'config' => $emptyConfig,
                ],
            ],
            'has permissions' => [
                'input' => [
                    'permission' => [
                        'permission'    => BasicPermissionMap::PERMISSION_VIEW,
                        'class'         => $this->accountUserClass,
                        'return'        => true,
                    ],
                    'mask' => [
                        'expects' => $this->once(),
                        'mask'    => EntityMaskBuilder::MASK_VIEW_LOCAL,
                        'class'   => $this->quoteClass,
                        'return'  => true,
                    ],
                    'config' => $config,
                ],
                'expected' => [
                    'config' => $config,
                ],
            ],
        ];
    }
}

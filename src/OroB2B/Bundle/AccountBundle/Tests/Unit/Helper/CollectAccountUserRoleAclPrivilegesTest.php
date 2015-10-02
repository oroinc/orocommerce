<?php

namespace OroB2B\src\OroB2B\Bundle\AccountBundle\Tests\Unit\Helper;

use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\SecurityBundle\Acl\Persistence\AclPrivilegeRepository;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;
use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainMetadataProvider;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;
use OroB2B\Bundle\AccountBundle\Helper\CollectAccountUserRoleAclPrivileges;

class CollectAccountUserRoleAclPrivilegesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AclPrivilegeRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $aclPrivilegeRepository;

    /**
     * @var AclManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $aclManager;

    /**
     * @var array
     */
    protected $extensionFilters = [
        'entity' => ['commerce'],
        'action' => ['commerce']
    ];

    /**
     * @var array
     */
    protected $privilegeConfig = [
        'entity' => ['types' => ['entity'], 'fix_values' => false, 'show_default' => true],
        'action' => ['types' => ['action'], 'fix_values' => false, 'show_default' => true],
        'default' => ['types' => ['(default)'], 'fix_values' => true, 'show_default' => false],
    ];

    /**
     * @var ChainMetadataProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $chainMetadataProvider;

    /**
     * @var CollectAccountUserRoleAclPrivileges
     */
    protected $accountUserRoleAclPrivilegeHelper;

    protected function setUp()
    {
        $this->aclManager = $this->getMockBuilder('\Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclPrivilegeRepository =
            $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Persistence\AclPrivilegeRepository')
                ->disableOriginalConstructor()
                ->getMock();

        $this->aclPrivilegeRepository->expects($this->atLeast(2))
            ->method('getPermissionNames')
            ->willReturn(['VIEW',  'CREATE', 'EDIT', 'DELETE', 'SHARE']);

        $this->chainMetadataProvider = $this->getMock('Oro\Bundle\SecurityBundle\Owner\Metadata\ChainMetadataProvider');

        $this->accountUserRoleAclPrivilegeHelper = new CollectAccountUserRoleAclPrivileges(
            $this->aclPrivilegeRepository,
            $this->aclManager,
            $this->chainMetadataProvider,
            $this->privilegeConfig
        );

        $this->accountUserRoleAclPrivilegeHelper->addExtensionFilter('entity', 'commerce');
    }

    /**
     * @param ArrayCollection $privileges
     * @param array $expected
     * @dataProvider collectDataProvider
     */
    public function testCollect(ArrayCollection $privileges, array $expected)
    {
        $role = new AccountUserRole('ROLE_ADMIN');

        $securityIdentity = new RoleSecurityIdentity($role);

        $this->aclManager->expects($this->once())
            ->method('getSid')
            ->with($role)
            ->willReturn($securityIdentity);

        $this->aclPrivilegeRepository->expects($this->once())
            ->method('getPrivileges')
            ->with($securityIdentity)
            ->willReturn($privileges);

        $this->chainMetadataProvider->expects($this->at(0))
            ->method('startProviderEmulation')
            ->with(FrontendOwnershipMetadataProvider::ALIAS);

        $this->chainMetadataProvider->expects($this->at(1))
            ->method('stopProviderEmulation');

        $result = $this->accountUserRoleAclPrivilegeHelper->collect($role);
        $actual = [];
        foreach ($result['data'] as $key => $value) {
            $actual[$key] = new ArrayCollection($value->getValues());
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function collectDataProvider()
    {
        $privilegesForEntity = [
            ['VIEW', 2],
            ['CREATE', 2],
            ['EDIT', 2],
            ['DELETE', 2],
            ['SHARE', 2]
        ];

        $privilegesForEntity2 = [
            ['VIEW', 222],
            ['CREATE', 2],
            ['EDIT', 2],
            ['DELETE', 2],
            ['SHARE', 2]
        ];

        $privilegesForAction = [
            ['EXECUTE', 5]
        ];

        return [
            'get and sorted privileges' => [
                'privileges' => $this->createPrivileges(
                    [
                        [
                            'total' => 10,
                            'extensionKey' => 'entity',
                            'identityName' => null,
                            'aclPermissions' => $privilegesForEntity
                        ],
                        [
                            'total' => 5,
                            'extensionKey' => 'action',
                            'identityName' => null,
                            'aclPermissions' => $privilegesForAction
                        ],
                        [
                            'total' => 3,
                            'extensionKey' => 'testExtension',
                            'identityName' => null,
                            'aclPermissions' => $privilegesForEntity
                        ],
                        [
                            'total' => 2,
                            'extensionKey' => '(default)',
                            'identityName' => '(default)',
                            'aclPermissions' => $privilegesForEntity
                        ],
                        [
                            'total' => 1,
                            'extensionKey' => '(default)',
                            'identityName' => null,
                            'aclPermissions' => $privilegesForEntity
                        ]
                    ]
                ),
                'expected' => [
                    'entity' => $this->createPrivileges([
                        [
                            'total' => 10,
                            'extensionKey' => 'entity',
                            'identityName' => null,
                            'aclPermissions' => $privilegesForEntity
                        ]
                    ]),
                    'action' => $this->createPrivileges([
                        [
                            'total' => 5,
                            'extensionKey' => 'action',
                            'identityName' => null,
                            'aclPermissions' => $privilegesForAction
                        ]
                    ]),
                    'default' => $this->createPrivileges([
                        [
                            'total' => 1,
                            'extensionKey' => '(default)',
                            'identityName' => null,
                            'aclPermissions' => $privilegesForEntity2
                        ]
                    ])
                ]
            ]
        ];
    }

    /**
     * @param array $config
     * @return array
     */
    protected function createPrivileges(array $config)
    {
        $privileges = new ArrayCollection();
        foreach ($config as $value) {
            for ($i = 1; $i <= $value['total']; $i++) {
                $privilege = new AclPrivilege();
                $privilege->setExtensionKey($value['extensionKey']);
                $identityName = $value['identityName'] ?: 'EntityClass_' . $i;
                $privilege->setIdentity(new AclPrivilegeIdentity($i, $identityName));
                $privilege->setGroup('commerce');
                foreach ($value['aclPermissions'] as $aclPermission) {
                    list($name, $accessLevel) = $aclPermission;
                    $privilege->addPermission(new AclPermission($name, $accessLevel));
                }
                $privileges->add($privilege);
            }
        }

        return $privileges;
    }
}

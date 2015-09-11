<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Security;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Security\AccountUserProvider;

class AccountUserProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AccountUserProvider
     */
    protected $provider;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var AclManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $aclManager;

    /**
     * @var UserInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $user;

    /**
     * @var string
     */
    protected $accountUserClass = 'OroB2B\Bundle\AccountBundle\Entity\AccountUser';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclManager = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->user = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');

        $this->provider = new AccountUserProvider(
            $this->securityFacade,
            $this->aclManager
        );
        $this->provider->setAccountUserClass($this->accountUserClass);
    }

    public function testGetLoggedUser()
    {
        /* @var $user AccountUser|\PHPUnit_Framework_MockObject_MockObject */
        $user = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\AccountUser');

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($user)
        ;

        $this->assertSame($user, $this->provider->getLoggedUser());
    }

    /**
     * @param array $inputData
     *
     * @dataProvider isGrantedBasicAndLocalWithFalseResultProvider
     */
    public function testIsGrantedBasicAndLocalWithFalseResult(array $inputData)
    {
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->willReturn($inputData['user']);
        ;
        $this->user->expects($this->never())
            ->method('getRoles')
        ;
        $this->aclManager->expects($this->never())
            ->method('getOid')
        ;
        $this->aclManager->expects($this->never())
            ->method('getSid')
        ;
        $this->aclManager->expects($this->never())
            ->method('getAces')
        ;

        $this->assertFalse(
            $this->provider->isGrantedViewBasic($inputData['class'])
        );

        $this->assertFalse(
            $this->provider->isGrantedViewLocal($inputData['class'])
        );

        $this->assertFalse(
            $this->provider->isGrantedEditBasic($inputData['class'])
        );

        $this->assertFalse(
            $this->provider->isGrantedEditLocal($inputData['class'])
        );
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider isGrantedViewBasicProvider
     */
    public function testIsGrantedViewBasic(array $inputData, array $expectedData)
    {
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->willReturn($this->user)
        ;
        $this->user->expects($this->once())
            ->method('getRoles')
            ->willReturn([$inputData['role']])
        ;
        $this->aclManager->expects($this->once())
            ->method('getOid')
            ->with($this->identicalTo($inputData['descriptor']))
            ->willReturn($inputData['oid'])
        ;
        $this->aclManager->expects($this->once())
            ->method('getSid')
            ->with($this->identicalTo($inputData['role']))
            ->willReturn($inputData['sid'])
        ;
        $this->aclManager->expects($this->once())
            ->method('getAces')
            ->with($this->identicalTo($inputData['sid']), $this->identicalTo($inputData['oid']))
            ->willReturn($inputData['aces'])
        ;

        $this->assertSame(
            $expectedData['isGranted'],
            $this->provider->isGrantedViewBasic($inputData['class'])
        );
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider isGrantedViewLocalProvider
     */
    public function testIsGrantedViewLocal(array $inputData, array $expectedData)
    {
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->willReturn($this->user)
        ;
        $this->user->expects($this->once())
            ->method('getRoles')
            ->willReturn([$inputData['role']])
        ;
        $this->aclManager->expects($this->once())
            ->method('getOid')
            ->with($this->identicalTo($inputData['descriptor']))
            ->willReturn($inputData['oid'])
        ;
        $this->aclManager->expects($this->once())
            ->method('getSid')
            ->with($this->identicalTo($inputData['role']))
            ->willReturn($inputData['sid'])
        ;
        $this->aclManager->expects($this->once())
            ->method('getAces')
            ->with($this->identicalTo($inputData['sid']), $this->identicalTo($inputData['oid']))
            ->willReturn($inputData['aces'])
        ;

        $this->assertSame(
            $expectedData['isGranted'],
            $this->provider->isGrantedViewLocal($inputData['class'])
        );
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider isGrantedEditBasicProvider
     */
    public function testIsGrantedEditBasic(array $inputData, array $expectedData)
    {
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->willReturn($this->user)
        ;
        $this->user->expects($this->once())
            ->method('getRoles')
            ->willReturn([$inputData['role']])
        ;
        $this->aclManager->expects($this->once())
            ->method('getOid')
            ->with($this->identicalTo($inputData['descriptor']))
            ->willReturn($inputData['oid'])
        ;
        $this->aclManager->expects($this->once())
            ->method('getSid')
            ->with($this->identicalTo($inputData['role']))
            ->willReturn($inputData['sid'])
        ;
        $this->aclManager->expects($this->once())
            ->method('getAces')
            ->with($this->identicalTo($inputData['sid']), $this->identicalTo($inputData['oid']))
            ->willReturn($inputData['aces'])
        ;

        $this->assertSame(
            $expectedData['isGranted'],
            $this->provider->isGrantedEditBasic($inputData['class'])
        );
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider isGrantedEditLocalProvider
     */
    public function testIsGrantedEditLocal(array $inputData, array $expectedData)
    {
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->willReturn($this->user)
        ;
        $this->user->expects($this->once())
            ->method('getRoles')
            ->willReturn([$inputData['role']])
        ;
        $this->aclManager->expects($this->once())
            ->method('getOid')
            ->with($this->identicalTo($inputData['descriptor']))
            ->willReturn($inputData['oid'])
        ;
        $this->aclManager->expects($this->once())
            ->method('getSid')
            ->with($this->identicalTo($inputData['role']))
            ->willReturn($inputData['sid'])
        ;
        $this->aclManager->expects($this->once())
            ->method('getAces')
            ->with($this->identicalTo($inputData['sid']), $this->identicalTo($inputData['oid']))
            ->willReturn($inputData['aces'])
        ;

        $this->assertSame(
            $expectedData['isGranted'],
            $this->provider->isGrantedEditLocal($inputData['class'])
        );
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider isGrantedViewAccountUserProvider
     */
    public function testIsGrantedViewAccountUser(array $inputData, array $expectedData)
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with($inputData['permission']['permission'], $inputData['permission']['descriptor'])
            ->willReturn($inputData['permission']['isGranted'])
        ;

        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->willReturn($this->user)
        ;
        $this->user->expects($this->any())
            ->method('getRoles')
            ->willReturn([$inputData['mask']['role']])
        ;
        $this->aclManager->expects($this->any())
            ->method('getOid')
            ->willReturn($inputData['mask']['oid'])
        ;
        $this->aclManager->expects($this->any())
            ->method('getSid')
            ->willReturn($inputData['mask']['sid'])
        ;
        $this->aclManager->expects($this->any())
            ->method('getAces')
            ->willReturn($inputData['mask']['aces'])
        ;

        $this->assertSame(
            $expectedData['isGranted'],
            $this->provider->isGrantedViewAccountUser($inputData['class'])
        );
    }

    /**
     * @return array
     */
    public function isGrantedBasicAndLocalWithFalseResultProvider()
    {
        return [
            'empty class' => [
                'input'=> [
                    'class' => '',
                    'user' => null,
                ],
            ],
            'empty user' => [
                'input'=> [
                    'class' => 'TestClass',
                    'user' => null,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function isGrantedViewBasicProvider()
    {
        return [
            'no access to class' => [
                'input' => [
                    'class' => 'TestClass1',
                    'role'  => 'ROLE1',
                    'descriptor' => 'entity:TestClass1',
                    'oid'   => new ObjectIdentity('entity', 'TestClass1'),
                    'sid'   => $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'),
                    'aces'  => [],
                ],
                'expected' => [
                    'isGranted' => false,
                    'oid'       => new ObjectIdentity('entity', 'TestClass1'),
                ],
            ],
            'granted access to class' => [
                'input' => [
                    'class' => 'TestClass2',
                    'role'  => 'ROLE2',
                    'descriptor' => 'entity:TestClass2',
                    'oid'       => new ObjectIdentity('entity', 'TestClass2'),
                    'sid'   => $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'),
                    'aces'  => [
                        $this->getEntry(EntityMaskBuilder::MASK_VIEW_DEEP, $this->once()),
                        $this->getEntry(EntityMaskBuilder::MASK_VIEW_BASIC, $this->once()),
                    ],
                ],
                'expected' => [
                    'isGranted' => true,
                ],
            ],
            'no access to object' => [
                'input' => [
                    'class' => new \stdClass(),
                    'role'  => 'ROLE3',
                    'descriptor' => 'entity:stdClass',
                    'oid'       => new ObjectIdentity('entity', 'stdClass'),
                    'sid'   => $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'),
                    'aces'  => [],
                ],
                'expected' => [
                    'isGranted' => false,
                ],
            ],
            'granted access to object' => [
                'input' => [
                    'class' => new \stdClass(),
                    'role'  => 'ROLE4',
                    'descriptor' => 'entity:stdClass',
                    'oid'   => new ObjectIdentity('entity', 'stdClass'),
                    'sid'   => $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'),
                    'aces'  => [
                        $this->getEntry(EntityMaskBuilder::MASK_VIEW_DEEP, $this->once()),
                        $this->getEntry(EntityMaskBuilder::MASK_VIEW_BASIC, $this->once()),
                    ],
                ],
                'expected' => [
                    'isGranted' => true,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function isGrantedViewLocalProvider()
    {
        return [
            'no access to class' => [
                'input' => [
                    'class' => 'TestClass1',
                    'role'  => 'ROLE1',
                    'descriptor' => 'entity:TestClass1',
                    'oid'   => new ObjectIdentity('entity', 'TestClass1'),
                    'sid'   => $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'),
                    'aces'  => [],
                ],
                'expected' => [
                    'isGranted' => false,
                    'oid'       => new ObjectIdentity('entity', 'TestClass1'),
                ],
            ],
            'granted access to class' => [
                'input' => [
                    'class' => 'TestClass2',
                    'role'  => 'ROLE2',
                    'descriptor' => 'entity:TestClass2',
                    'oid'       => new ObjectIdentity('entity', 'TestClass2'),
                    'sid'   => $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'),
                    'aces'  => [
                        $this->getEntry(EntityMaskBuilder::MASK_VIEW_DEEP, $this->once()),
                        $this->getEntry(EntityMaskBuilder::MASK_VIEW_LOCAL, $this->once()),
                    ],
                ],
                'expected' => [
                    'isGranted' => true,
                ],
            ],
            'no access to object' => [
                'input' => [
                    'class' => new \stdClass(),
                    'role'  => 'ROLE3',
                    'descriptor' => 'entity:stdClass',
                    'oid'       => new ObjectIdentity('entity', 'stdClass'),
                    'sid'   => $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'),
                    'aces'  => [],
                ],
                'expected' => [
                    'isGranted' => false,
                ],
            ],
            'granted access to object' => [
                'input' => [
                    'class' => new \stdClass(),
                    'role'  => 'ROLE4',
                    'descriptor' => 'entity:stdClass',
                    'oid'   => new ObjectIdentity('entity', 'stdClass'),
                    'sid'   => $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'),
                    'aces'  => [
                        $this->getEntry(EntityMaskBuilder::MASK_VIEW_DEEP, $this->once()),
                        $this->getEntry(EntityMaskBuilder::MASK_VIEW_LOCAL, $this->once()),
                    ],
                ],
                'expected' => [
                    'isGranted' => true,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function isGrantedEditBasicProvider()
    {
        return [
            'no access to class' => [
                'input' => [
                    'class' => 'TestClass1',
                    'role'  => 'ROLE1',
                    'descriptor' => 'entity:TestClass1',
                    'oid'   => new ObjectIdentity('entity', 'TestClass1'),
                    'sid'   => $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'),
                    'aces'  => [],
                ],
                'expected' => [
                    'isGranted' => false,
                    'oid'       => new ObjectIdentity('entity', 'TestClass1'),
                ],
            ],
            'granted access to class' => [
                'input' => [
                    'class' => 'TestClass2',
                    'role'  => 'ROLE2',
                    'descriptor' => 'entity:TestClass2',
                    'oid'       => new ObjectIdentity('entity', 'TestClass2'),
                    'sid'   => $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'),
                    'aces'  => [
                        $this->getEntry(EntityMaskBuilder::MASK_EDIT_DEEP, $this->once()),
                        $this->getEntry(EntityMaskBuilder::MASK_EDIT_BASIC, $this->once()),
                    ],
                ],
                'expected' => [
                    'isGranted' => true,
                ],
            ],
            'no access to object' => [
                'input' => [
                    'class' => new \stdClass(),
                    'role'  => 'ROLE3',
                    'descriptor' => 'entity:stdClass',
                    'oid'       => new ObjectIdentity('entity', 'stdClass'),
                    'sid'   => $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'),
                    'aces'  => [],
                ],
                'expected' => [
                    'isGranted' => false,
                ],
            ],
            'granted access to object' => [
                'input' => [
                    'class' => new \stdClass(),
                    'role'  => 'ROLE4',
                    'descriptor' => 'entity:stdClass',
                    'oid'   => new ObjectIdentity('entity', 'stdClass'),
                    'sid'   => $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'),
                    'aces'  => [
                        $this->getEntry(EntityMaskBuilder::MASK_EDIT_DEEP, $this->once()),
                        $this->getEntry(EntityMaskBuilder::MASK_EDIT_BASIC, $this->once()),
                    ],
                ],
                'expected' => [
                    'isGranted' => true,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function isGrantedEditLocalProvider()
    {
        return [
            'no access to class' => [
                'input' => [
                    'class' => 'TestClass1',
                    'role'  => 'ROLE1',
                    'descriptor' => 'entity:TestClass1',
                    'oid'   => new ObjectIdentity('entity', 'TestClass1'),
                    'sid'   => $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'),
                    'aces'  => [],
                ],
                'expected' => [
                    'isGranted' => false,
                    'oid'       => new ObjectIdentity('entity', 'TestClass1'),
                ],
            ],
            'granted access to class' => [
                'input' => [
                    'class' => 'TestClass2',
                    'role'  => 'ROLE2',
                    'descriptor' => 'entity:TestClass2',
                    'oid'       => new ObjectIdentity('entity', 'TestClass2'),
                    'sid'   => $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'),
                    'aces'  => [
                        $this->getEntry(EntityMaskBuilder::MASK_EDIT_DEEP, $this->once()),
                        $this->getEntry(EntityMaskBuilder::MASK_EDIT_LOCAL, $this->once()),
                    ],
                ],
                'expected' => [
                    'isGranted' => true,
                ],
            ],
            'no access to object' => [
                'input' => [
                    'class' => new \stdClass(),
                    'role'  => 'ROLE3',
                    'descriptor' => 'entity:stdClass',
                    'oid'       => new ObjectIdentity('entity', 'stdClass'),
                    'sid'   => $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'),
                    'aces'  => [],
                ],
                'expected' => [
                    'isGranted' => false,
                ],
            ],
            'granted access to object' => [
                'input' => [
                    'class' => new \stdClass(),
                    'role'  => 'ROLE4',
                    'descriptor' => 'entity:stdClass',
                    'oid'   => new ObjectIdentity('entity', 'stdClass'),
                    'sid'   => $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'),
                    'aces'  => [
                        $this->getEntry(EntityMaskBuilder::MASK_EDIT_DEEP, $this->once()),
                        $this->getEntry(EntityMaskBuilder::MASK_EDIT_LOCAL, $this->once()),
                    ],
                ],
                'expected' => [
                    'isGranted' => true,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function isGrantedViewAccountUserProvider()
    {
        return [
            '!AccountUser::VIEW_LOCAL && !Entity::VIEW_LOCAL' => [
                'input' => [
                    'class' => 'TestClass1',
                    'permission' => [
                        'permission' => BasicPermissionMap::PERMISSION_VIEW,
                        'descriptor' => 'entity:commerce@' . $this->accountUserClass,
                        'isGranted'  => false,
                    ],
                    'mask' => [
                        'role'  => 'ROLE1',
                        'oid'   => new ObjectIdentity('entity', 'TestClass1'),
                        'sid'   => $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'),
                        'aces'  => [],
                    ]
                ],
                'expected' => [
                    'isGranted' => false,
                ],
            ],
            '!AccountUser::VIEW_LOCAL && Entity::VIEW_LOCAL' => [
                'input' => [
                    'class' => 'TestClass1',
                    'permission' => [
                        'permission' => BasicPermissionMap::PERMISSION_VIEW,
                        'descriptor' => 'entity:commerce@' . $this->accountUserClass,
                        'isGranted'  => false,
                    ],
                    'mask' => [
                        'role'  => 'ROLE1',
                        'oid'   => new ObjectIdentity('entity', 'TestClass1'),
                        'sid'   => $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'),
                        'aces'  => [
                            $this->getEntry(EntityMaskBuilder::MASK_VIEW_LOCAL, $this->once()),
                        ],
                    ]
                ],
                'expected' => [
                    'isGranted' => false,
                ],
            ],
            'AccountUser::VIEW_LOCAL && !Entity::VIEW_LOCAL' => [
                'input' => [
                    'class' => 'TestClass1',
                    'permission' => [
                        'permission' => BasicPermissionMap::PERMISSION_VIEW,
                        'descriptor' => 'entity:commerce@' . $this->accountUserClass,
                        'isGranted'  => true,
                    ],
                    'mask' => [
                        'role'  => 'ROLE1',
                        'oid'   => new ObjectIdentity('entity', 'TestClass1'),
                        'sid'   => $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'),
                        'aces'  => [],
                    ]
                ],
                'expected' => [
                    'isGranted' => false,
                ],
            ],
            'AccountUser::VIEW_LOCAL && Entity::VIEW_LOCAL' => [
                'input' => [
                    'class' => 'TestClass1',
                    'permission' => [
                        'permission' => BasicPermissionMap::PERMISSION_VIEW,
                        'descriptor' => 'entity:commerce@' . $this->accountUserClass,
                        'isGranted'  => true,
                    ],
                    'mask' => [
                        'role'  => 'ROLE1',
                        'oid'   => new ObjectIdentity('entity', 'TestClass1'),
                        'sid'   => $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'),
                        'aces'  => [
                            $this->getEntry(EntityMaskBuilder::MASK_VIEW_LOCAL, $this->once()),
                        ],
                    ]
                ],
                'expected' => [
                    'isGranted' => true,
                ],
            ],
        ];
    }

    /**
     * @param int $mask
     * @param \PHPUnit_Framework_MockObject_Matcher_Invocation $expects
     * @return EntryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEntry($mask, \PHPUnit_Framework_MockObject_Matcher_Invocation $expects)
    {
        /* @var $entry EntryInterface|\PHPUnit_Framework_MockObject_MockObject */
        $entry = $this->getMock('Symfony\Component\Security\Acl\Model\EntryInterface');
        $entry->expects($expects)
            ->method('getMask')
            ->willReturn($mask)
        ;
        return $entry;
    }
}

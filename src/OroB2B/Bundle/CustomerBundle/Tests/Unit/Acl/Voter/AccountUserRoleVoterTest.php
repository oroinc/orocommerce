<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Acl\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\CustomerBundle\Acl\Voter\AccountUserRoleVoter;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;

class AccountUserRoleVoterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AccountUserRoleVoter
     */
    protected $voter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->voter = new AccountUserRoleVoter($this->doctrineHelper);
    }

    protected function tearDown()
    {
        unset($this->voter, $this->doctrineHelper);
    }

    /**
     * @param string $class
     * @param string $actualClass
     * @param bool $expected
     *
     * @dataProvider supportsClassDataProvider
     */
    public function testSupportsClass($class, $actualClass, $expected)
    {
        $this->voter->setClassName($actualClass);
        $this->assertEquals($expected, $this->voter->supportsClass($class));
    }

    /**
     * @return array
     */
    public function supportsClassDataProvider()
    {
        return [
            'supported class' => ['stdClass', 'stdClass', true],
            'not supported class' => ['NotSupportedClass', 'stdClass', false]
        ];
    }

    /**
     * @param string $attribute
     * @param bool $expected
     *
     * @dataProvider supportsAttributeDataProvider
     */
    public function testSupportsAttribute($attribute, $expected)
    {
        $this->assertEquals($expected, $this->voter->supportsAttribute($attribute));
    }

    /**
     * @return array
     */
    public function supportsAttributeDataProvider()
    {
        return [
            'VIEW' => ['VIEW', false],
            'CREATE' => ['CREATE', false],
            'EDIT' => ['EDIT', false],
            'DELETE' => ['DELETE', true],
            'ASSIGN' => ['ASSIGN', false]
        ];
    }

    /**
     * @param bool $isDefaultWebsiteRole
     * @param bool $hasUsers
     * @param int $expected
     *
     * @dataProvider attributesDataProvider
     */
    public function testVote($isDefaultWebsiteRole, $hasUsers, $expected)
    {
        $object = new AccountUserRole();

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->with($object)
            ->will($this->returnValue(get_class($object)));
        $this->voter->setClassName(get_class($object));

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->will($this->returnValue(1));

        $entityRepository = $this
            ->getMockBuilder('OroB2B\Bundle\CustomerBundle\Entity\Repository\AccountUserRoleRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepository->expects($this->at(0))
            ->method('isDefaultForWebsite')
            ->will($this->returnValue($isDefaultWebsiteRole));

        $entityRepository->expects($this->at(1))
            ->method('hasAssignedUsers')
            ->will($this->returnValue($hasUsers));

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with('OroB2BCustomerBundle:AccountUserRole')
            ->will($this->returnValue($entityRepository));

        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface $token */
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $this->assertEquals(
            $expected,
            $this->voter->vote($token, $object, ['DELETE'])
        );
    }

    /**
     * @return array
     */
    public function attributesDataProvider()
    {
        return [
            'common role' => [
                'isDefaultWebsiteRole' => false,
                'hasUsers' => false,
                'expected' => VoterInterface::ACCESS_ABSTAIN
            ],
            'default website role' => [
                'isDefaultWebsiteRole' => true,
                'hasUsers' => false,
                'expected' => VoterInterface::ACCESS_DENIED
            ],
            'role wit users' => [
                'isDefaultWebsiteRole' => false,
                'hasUsers' => true,
                'expected' => VoterInterface::ACCESS_DENIED
            ]
        ];
    }
}

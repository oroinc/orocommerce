<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebCatalogBundle\Acl\Voter\WebCatalogVoter;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogUsageProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class WebCatalogVoterTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var WebCatalogVoter
     */
    protected $voter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var WebCatalogUsageProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $usageProvider;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->usageProvider = $this->getMockBuilder(WebCatalogUsageProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->voter = new WebCatalogVoter($this->doctrineHelper, $this->usageProvider);
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

    public function testVoteAbstain()
    {
        $object = $this->getEntity(WebCatalog::class, ['id' => 1]);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->with($object)
            ->will($this->returnValue(get_class($object)));
        $this->voter->setClassName(WebCatalog::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->will($this->returnValue(1));

        $this->usageProvider->expects($this->once())
            ->method('isInUse')
            ->with($object)
            ->willReturn(false);

        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface $token */
        $token = $this->getMock(TokenInterface::class);
        $this->assertEquals(
            WebCatalogVoter::ACCESS_ABSTAIN,
            $this->voter->vote($token, $object, ['DELETE'])
        );
    }

    public function testVoteDeny()
    {
        $object = $this->getEntity(WebCatalog::class, ['id' => 1]);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->with($object)
            ->will($this->returnValue(get_class($object)));
        $this->voter->setClassName(WebCatalog::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->will($this->returnValue(1));

        $this->usageProvider->expects($this->once())
            ->method('isInUse')
            ->with($object)
            ->willReturn(true);

        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface $token */
        $token = $this->getMock(TokenInterface::class);
        $this->assertEquals(
            WebCatalogVoter::ACCESS_DENIED,
            $this->voter->vote($token, $object, ['DELETE'])
        );
    }
}

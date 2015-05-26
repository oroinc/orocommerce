<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Acl\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Acl\Voter\PriceListVoter;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class PriceListVoterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceListVoter
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

        $this->voter = new PriceListVoter($this->doctrineHelper);
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
     * @param object $object
     * @param int $expected
     *
     * @dataProvider attributesDataProvider
     */
    public function testVote($object, $expected)
    {
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->with($object)
            ->will($this->returnValue(get_class($object)));
        $this->voter->setClassName(get_class($object));

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->will($this->returnValue(1));

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
            [$this->getPriceList(false), 0],
            [$this->getPriceList(true), -1]
        ];
    }

    /**
     * @param bool $isDefault
     * @return PriceList
     */
    protected function getPriceList($isDefault)
    {
        $priceList = new PriceList();
        $priceList->setDefault($isDefault);

        return $priceList;
    }
}

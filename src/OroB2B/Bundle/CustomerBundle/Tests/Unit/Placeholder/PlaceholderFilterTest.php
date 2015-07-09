<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Placeholder;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Placeholder\PlaceholderFilter;

class PlaceholderFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PlaceholderFilter
     */
    protected $placeholderFilter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    protected $securityFacade;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->placeholderFilter = new PlaceholderFilter($this->securityFacade);
    }

    protected function tearDown()
    {
        unset($this->placeholderFilter, $this->securityFacade);
    }

    /**
     * @dataProvider isUserApplicableDataProvider
     *
     * @param object|string $user
     * @param bool $expected
     */
    public function testIsUserApplicable($user, $expected)
    {
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($user);

        $this->assertEquals($expected, $this->placeholderFilter->isUserApplicable());
    }

    /**
     * @return array
     */
    public function isUserApplicableDataProvider()
    {
        return [
            [new \stdClass(), false],
            [new AccountUser(), true]
        ];
    }

    /**
     * @dataProvider isLoginRequiredDataProvider
     *
     * @param mixed $user
     * @param bool $expected
     */
    public function testIsLoginRequired($user, $expected)
    {
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($user);

        $this->assertEquals($expected, $this->placeholderFilter->isLoginRequired());
    }

    /**
     * @return array
     */
    public function isLoginRequiredDataProvider()
    {
        return [
            ['none', true],
            [new AccountUser(), false]
        ];
    }

    /**
     * @dataProvider isFrontendApplicableDataProvider
     *
     * @param object $user
     * @param bool $expected
     */
    public function testIsFrontendApplicable($user, $expected)
    {
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($user);

        $this->assertEquals($expected, $this->placeholderFilter->isFrontendApplicable());
    }

    /**
     * @return array
     */
    public function isFrontendApplicableDataProvider()
    {
        return [
            'anonymous' => ['none', true],
            'not valid user' => [new \stdClass(), false],
            'valid user' => [new AccountUser(), true]
        ];
    }
}

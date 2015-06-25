<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Owner;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Owner\EntityOwnershipDecisionMaker;

class EntityOwnershipDecisionMakerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OwnershipMetadataProvider
     */
    protected $metadataProvider;

    /**
     * @var OwnerTreeProvider
     */
    protected $treeProvider;

    /**
     * @var EntityOwnershipDecisionMaker
     */
    protected $decisionMaker;

    protected function setUp()
    {
        $this->treeProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadataProvider = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->decisionMaker = new EntityOwnershipDecisionMaker(
            $this->treeProvider,
            new ObjectIdAccessor(),
            new EntityOwnerAccessor($this->metadataProvider),
            $this->metadataProvider
        );
    }

    /**
     * @dataProvider supportsDataProvider
     *
     * @param SecurityFacade|null $securityFacade
     * @param bool $expectedResult
     */
    public function testSupports($securityFacade, $expectedResult)
    {
        if ($securityFacade) {
            $this->decisionMaker->setSecurityFacade($securityFacade);
        }
        $this->assertEquals($expectedResult, $this->decisionMaker->supports());
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        $securityFacadeWithoutAccountUser = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $securityFacadeWithoutAccountUser->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn(new \stdClass());

        $securityFacadeWithAccountUser = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $securityFacadeWithAccountUser->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn(new AccountUser());

        return [
            'without security facade' => [
                'securityFacade' => null,
                'expectedResult' => false
            ],
            'security facade with incorrect user class' => [
                'securityFacade' => $securityFacadeWithoutAccountUser,
                'expectedResult' => false
            ],
            'security facade with user class' => [
                'securityFacade' => $securityFacadeWithAccountUser,
                'expectedResult' => true
            ]
        ];
    }
}

<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Acl\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Acl\Voter\AccountGroupVoter;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;

class AccountGroupVoterTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const DEFAULT_GROUP_ID = 1;

    /**
     * @var AccountGroupVoter
     */
    protected $voter;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject $doctrineHelper */
        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->willReturnCallback(function ($group) {
                return $group instanceof AccountGroup ? $group->getId() : null;
            });

        $doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(function ($group) {
                return get_class($group);
            });

        /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject $configManager */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->any())
            ->method('get')
            ->with('oro_b2b_account.anonymous_account_group')
            ->willReturn(self::DEFAULT_GROUP_ID);

        $this->voter = new AccountGroupVoter($doctrineHelper);
        $this->voter->setClassName('OroB2B\Bundle\AccountBundle\Entity\AccountGroup');
        $this->voter->setConfigManager($configManager);
    }

    /**
     * @dataProvider voteDataProvider
     * @param object $object
     * @param int $result
     * @param string $attribute
     */
    public function testVote($object, $result, $attribute)
    {
        $this->assertSame($result, $this->voter->vote($this->getToken(), $object, [$attribute]));
    }

    /**
     * @return array
     */
    public function voteDataProvider()
    {
        return [
            'denied when default group' => [
                'object' => $this->getGroup(self::DEFAULT_GROUP_ID),
                'result' => VoterInterface::ACCESS_DENIED,
                'attribute' => 'DELETE',
            ],
            'abstain when not default group' => [
                'object' => $this->getGroup(2),
                'result' => VoterInterface::ACCESS_ABSTAIN,
                'attribute' => 'DELETE',
            ],
            'abstain when not supported attribute' => [
                'object' => $this->getGroup(2),
                'result' => VoterInterface::ACCESS_ABSTAIN,
                'attribute' => 'VIEW',
            ],
            'abstain when another entity' => [
                'object' => new Account(),
                'result' => VoterInterface::ACCESS_ABSTAIN,
                'attribute' => 'DELETE',
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenInterface
     */
    protected function getToken()
    {
        return $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param int $id
     * @return AccountGroup
     */
    protected function getGroup($id)
    {
        return $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', ['id' => $id]);
    }
}

<?php
namespace Oro\Bundle\AccountBundle\Tests\Unit\Event;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use Oro\Bundle\AccountBundle\Event\RecordOwnerDataListener;
use Oro\Bundle\AccountBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity;
use Oro\Bundle\EntityConfigBundle\Config\Config;

class RecordOwnerDataListenerTest extends \PHPUnit_Framework_TestCase
{
    /**  @var RecordOwnerDataListener */
    protected $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityContext;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    protected function setUp()
    {
        $serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityContext = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContext')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceLink->expects($this->any())->method('getService')
            ->will($this->returnValue($this->securityContext));
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new RecordOwnerDataListener($serviceLink, $this->configProvider);
    }

    /**
     * @param $token
     * @param $securityConfig
     * @param $expect
     *
     * @dataProvider preSetData
     */
    public function testPrePersistUser($token, $securityConfig, $expect)
    {
        $entity = new Entity();
        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $args = new LifecycleEventArgs($entity, $this->getMock('Doctrine\Common\Persistence\ObjectManager'));
        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->will($this->returnValue(true));
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($securityConfig));

        $this->listener->prePersist($args);
        if (isset($expect['owner'])) {
            $this->assertEquals($expect['owner'], $entity->getOwner());
        } else {
            $this->assertNull($entity->getOwner());
        }
    }

    /**
     * @return array
     */
    public function preSetData()
    {
        $entityConfigId = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId')
            ->disableOriginalConstructor()
            ->getMock();

        $user = new User();
        $user->setId(1);

        $userConfig = new Config($entityConfigId);
        $userConfig->setValues(
            [
                "frontend_owner_type" => "FRONTEND_USER",
                "frontend_owner_field_name" => "owner",
                "frontend_owner_column_name" => "owner_id"
            ]
        );
        $buConfig = new Config($entityConfigId);
        $buConfig->setValues(
            [
                "frontend_owner_type" => "FRONTEND_BUSINESS_UNIT",
                "frontend_owner_field_name" => "owner",
                "frontend_owner_column_name" => "owner_id"
            ]
        );
        $organizationConfig = new Config($entityConfigId);
        $organizationConfig->setValues(
            [
                "frontend_owner_type" => "FRONTEND_ORGANIZATION",
                "frontend_owner_field_name" => "owner",
                "frontend_owner_column_name" => "owner_id"
            ]
        );

        return [
            'OwnershipType User with UsernamePasswordToken' => [
                new UsernamePasswordToken($user, 'admin', 'key'),
                $userConfig,
                ['owner' => $user]
            ],
            'OwnershipType BusinessUnit with UsernamePasswordToken' => [
                new UsernamePasswordToken($user, 'admin', 'key'),
                $buConfig,
                []

            ],
            'OwnershipType Organization with UsernamePasswordToken' => [
                new UsernamePasswordToken($user, 'admin', 'key'),
                $organizationConfig,
                []
            ],
        ];
    }
}

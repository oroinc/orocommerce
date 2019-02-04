<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\EventListener\RemoveFromConfigurationConsentEntityListener;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfigManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class RemoveFromConfigurationConsentEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConsentConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $consentConfigManager;

    /**
     * @var RemoveFromConfigurationConsentEntityListener
     */
    private $listener;

    /**
     * @var ObjectRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $repository;

    protected function setUp()
    {
        $this->repository = $this->createMock(ObjectRepository::class);
        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects($this->once())
            ->method('getRepository')
            ->with(Website::class)
            ->willReturn($this->repository);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($objectManager);
        $this->consentConfigManager = $this->createMock(ConsentConfigManager::class);

        $this->listener = new RemoveFromConfigurationConsentEntityListener(
            $doctrine,
            $this->consentConfigManager
        );
    }

    public function tearDown()
    {
        unset(
            $this->listener,
            $this->consentConfigManager,
            $this->repository
        );
    }

    /**
     * @return array
     */
    public function preRemoveProvider()
    {
        return [
            "two websites changes" => [
                "websites" => [
                    new Website(),
                    new Website()
                ],
                "expected_method_call" => 2
            ],
            "no websites changes" => [
                "websites" => [],
                "expected_method_call" => 0
            ]
        ];
    }

    /**
     * @dataProvider preRemoveProvider
     *
     * @param $websites
     * @param $expectedCalls
     */
    public function testPreRemove($websites, $expectedCalls)
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($websites);

        $this->consentConfigManager->expects($this->exactly($expectedCalls))
            ->method('updateConsentsConfigForWebsiteScope');

        $this->consentConfigManager->expects($this->once())
            ->method('updateConsentsConfigForGlobalScope');

        $this->listener->preRemove(new Consent());
    }
}

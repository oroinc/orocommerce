<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Helper;

use Oro\Bundle\ConsentBundle\Helper\ConsentContextInitializeHelper;
use Oro\Bundle\ConsentBundle\Provider\ConsentContextProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\Unit\EntityTrait;

class ConsentContextInitializerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ConsentContextProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $consentContextProvider;

    /**
     * @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteManager;

    /**
     * @var ConsentContextInitializeHelper
     */
    private $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->consentContextProvider = $this->createMock(ConsentContextProvider::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->helper = new ConsentContextInitializeHelper(
            $this->consentContextProvider,
            $this->websiteManager
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->consentContextProvider,
            $this->websiteManager,
            $this->helper
        );
    }

    /**
     * @dataProvider initializeProvider
     *
     * @param bool              $contextIsInitialized
     * @param CustomerUser|null $customerUser
     * @param Website|null      $currentWebsite
     * @param bool              $expectInitialization
     * @param array             $initializationParams
     * @param bool              $expectResult
     */
    public function testInitialize(
        bool $contextIsInitialized,
        $customerUser,
        $currentWebsite,
        bool $expectInitialization,
        array $initializationParams,
        bool $expectResult
    ) {
        $this->consentContextProvider
            ->expects($this->any())
            ->method('isInitialized')
            ->willReturn($contextIsInitialized);

        $this->websiteManager
            ->expects($this->any())
            ->method('getCurrentWebsite')
            ->willReturn($currentWebsite);

        if ($expectInitialization) {
            $this->consentContextProvider
                ->expects($this->once())
                ->method('initializeContext')
                ->with(...$initializationParams);
        } else {
            $this->consentContextProvider
                ->expects($this->never())
                ->method('initializeContext');
        }

        $this->assertEquals($expectResult, $this->helper->initialize($customerUser));
    }

    /**
     * @return array
     */
    public function initializeProvider()
    {
        $currentWebsite = $this->getEntity(Website::class, ['id' => 11]);
        $customerUserWebsite = $this->getEntity(Website::class, ['id' => 22]);
        $customerUserWithoutWebsite = $this->getEntity(CustomerUser::class, ['id' => 1]);
        $customerUserWithWebsite = $this->getEntity(
            CustomerUser::class,
            [
                'id' => 1,
                'website' => $customerUserWebsite
            ]
        );

        return [
            "Context is initialized" => [
                'contextIsInitialized' => true,
                'customerUser' => null,
                'currentWebsite' => $currentWebsite,
                'expectInitialization' => false,
                'initializationParams' => [],
                'expectResult' => true
            ],
            "User is null" => [
                'contextIsInitialized' => false,
                'customerUser' => null,
                'currentWebsite' => $currentWebsite,
                'expectInitialization' => true,
                'initializationParams' => [
                    $currentWebsite,
                    null
                ],
                'expectResult' => true
            ],
            "User doesn't contain website" => [
                'contextIsInitialized' => false,
                'customerUser' => $customerUserWithoutWebsite,
                'currentWebsite' => $currentWebsite,
                'expectInitialization' => true,
                'initializationParams' => [
                    $currentWebsite,
                    $customerUserWithoutWebsite
                ],
                'expectResult' => true
            ],
            "User contains website" => [
                'contextIsInitialized' => false,
                'customerUser' => $customerUserWithWebsite,
                'currentWebsite' => $currentWebsite,
                'expectInitialization' => true,
                'initializationParams' => [
                    $customerUserWebsite,
                    $customerUserWithWebsite
                ],
                'expectResult' => true
            ],
            "Can't resolve website" => [
                'contextIsInitialized' => false,
                'customerUser' => $customerUserWithoutWebsite,
                'currentWebsite' => null,
                'expectInitialization' => false,
                'initializationParams' => [],
                'expectResult' => false
            ]
        ];
    }
}

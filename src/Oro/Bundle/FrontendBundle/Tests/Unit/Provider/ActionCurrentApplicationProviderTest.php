<?php

namespace Oro\Bundle\FrontendBundle\Tests\Unit\Provider;

use Oro\Bundle\ActionBundle\Tests\Unit\Provider\CurrentApplicationProviderTest;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\FrontendBundle\Provider\ActionCurrentApplicationProvider;
use Oro\Bundle\UserBundle\Entity\User;

class ActionCurrentApplicationProviderTest extends CurrentApplicationProviderTest
{
    /** @var ActionCurrentApplicationProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->provider = new ActionCurrentApplicationProvider($this->provider, $this->tokenStorage);
    }

    /**
     * @return array
     */
    public function getCurrentApplicationProvider()
    {
        return [
            'backend user' => [
                'token' => $this->createToken(new User(), $this->exactly(2)),
                'expectedResult' => 'default',
            ],
            'frontend user' => [
                'token' => $this->createToken(new AccountUser()),
                'expectedResult' => 'commerce',
            ],
            'not supported user' => [
                'token' => $this->createToken('anon.', $this->exactly(2)),
                'expectedResult' => null,
            ],
            'empty token' => [
                'token' => null,
                'expectedResult' => null,
            ],
        ];
    }

    /**
     * @return array
     */
    public function isApplicationsValidDataProvider()
    {
        $user = new User();
        $accountUser = new AccountUser();
        $otherUser = 'anon.';

        return [
            [
                'applications' => ['default', 'commerce'],
                'token' => $this->createToken($user, $this->exactly(2)),
                'expectedResult' => true
            ],
            [
                'applications' => ['default', 'commerce'],
                'token' => $this->createToken($accountUser),
                'expectedResult' => true
            ],
            [
                'applications' => ['default'],
                'token' => $this->createToken($user, $this->exactly(2)),
                'expectedResult' => true
            ],
            [
                'applications' => ['default'],
                'token' => $this->createToken($accountUser),
                'expectedResult' => false
            ],
            [
                'applications' => ['commerce'],
                'token' => $this->createToken($user, $this->exactly(2)),
                'expectedResult' => false
            ],
            [
                'applications' => ['commerce'],
                'token' => $this->createToken($accountUser),
                'expectedResult' => true
            ],
            [
                'applications' => ['default'],
                'token' => $this->createToken($otherUser, $this->exactly(2)),
                'expectedResult' => false
            ],
            [
                'applications' => ['commerce'],
                'token' => $this->createToken($otherUser, $this->exactly(2)),
                'expectedResult' => false
            ],
            [
                'applications' => ['default', 'commerce'],
                'token' => null,
                'expectedResult' => false
            ],
            [
                'applications' => [],
                'token' => null,
                'expectedResult' => true
            ],
        ];
    }
}

<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Oro\Bundle\CMSBundle\Provider\HTMLPurifierScopeProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Role\Role;

class HTMLPurifierScopeProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider testGetScopeDataProvider
     * @param string $mode
     * @param array $restrictions
     * @param string|null $expected
     */
    public function testGetScope(string $mode, array $restrictions, ?string $expected): void
    {
        $role = new Role('ROLE_FOO');

        $user = $this->createMock(User::class);
        $user->expects($this->any())
            ->method('getRoles')
            ->willReturn([$role]);

        /** @var TokenAccessor|\PHPUnit\Framework\MockObject\MockObject $tokenAccessor */
        $tokenAccessor = $this->createMock(TokenAccessor::class);
        $tokenAccessor->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $provider = new HTMLPurifierScopeProvider($tokenAccessor, $mode, $restrictions);
        $provider->addScopeMapping('secure', 'default');
        $provider->addScopeMapping('selective', 'lax');
        $provider->addScopeMapping('unsecure', null);

        $this->assertEquals($expected, $provider->getScope(\stdClass::class, 'field'));
    }

    /**
     * @return array
     */
    public function testGetScopeDataProvider(): array
    {
        return [
            'secure mode without restrictions' => [
                'mode' => 'secure',
                'restrictions' => [],
                'expected' => 'default'
            ],
            'secure mode with restrictions' => [
                'mode' => 'secure',
                'restrictions' =>  [
                    'ROLE_FOO' => [
                        \stdClass::class => [
                            'field'
                        ]
                    ]
                ],
                'expected' => 'default'
            ],
            'selective mode with restrictions' => [
                'mode' => 'selective',
                'restrictions' => [
                    'ROLE_FOO' => [
                        \stdClass::class => [
                            'field'
                        ]
                    ]
                ],
                'expected' => 'lax'
            ],
            'selective mode without restrictions' => [
                'mode' => 'selective',
                'restrictions' => [],
                'expected' => 'default'
            ],
            'unsecure mode without restrictions' => [
                'mode' => 'unsecure',
                'restrictions' => [],
                'expected' => null
            ],
            'unsecure mode with restrictions' => [
                'mode' => 'unsecure',
                'restrictions' => [],
                'expected' => null
            ]
        ];
    }
}

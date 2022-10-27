<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Oro\Bundle\CMSBundle\Provider\HTMLPurifierScopeProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class HTMLPurifierScopeProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @dataProvider testGetScopeDataProvider
     */
    public function testGetScope(string $mode, array $restrictions, ?string $expected): void
    {
        $role = 'ROLE_FOO';

        $token = $this->createMock(AbstractToken::class);
        $token->expects(self::any())
            ->method('getRoleNames')
            ->willReturn([$role]);

        $tokenAccessor = $this->createMock(TokenAccessor::class);
        $tokenAccessor->expects(self::any())
            ->method('getToken')
            ->willReturn($token);

        $provider = new HTMLPurifierScopeProvider($tokenAccessor, $mode, $restrictions);
        $provider->addScopeMapping('secure', 'default');
        $provider->addScopeMapping('selective', 'lax');
        $provider->addScopeMapping('unsecure', null);

        self::assertEquals($expected, $provider->getScope(\stdClass::class, 'field'));
    }

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

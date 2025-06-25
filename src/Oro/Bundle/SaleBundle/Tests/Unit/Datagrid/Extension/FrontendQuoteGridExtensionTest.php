<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Datagrid\Extension;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\SaleBundle\Datagrid\Extension\FrontendQuoteGridExtension;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class FrontendQuoteGridExtensionTest extends TestCase
{
    private FrontendQuoteGridExtension $extension;
    private TokenAccessorInterface&MockObject $tokenAccessor;
    private FrontendHelper&MockObject $frontendHelper;


    #[\Override]
    protected function setUp(): void
    {
        $this->tokenAccessor = self::createMock(TokenAccessorInterface::class);
        $this->frontendHelper = self::createMock(FrontendHelper::class);

        $this->extension = new FrontendQuoteGridExtension(
            $this->tokenAccessor,
            $this->frontendHelper
        );
        $this->extension->setParameters(new ParameterBag([]));
    }


    /**
     * @dataProvider isApplicableDataProvider
     */
    public function testIsApplicable(
        array $gridConfig,
        bool $frontendRequest,
        TokenInterface $token,
        bool $expected
    ) {
        $configuration = DatagridConfiguration::create($gridConfig);

        $this->tokenAccessor->expects(self::any())
            ->method('getToken')
            ->willReturn($token);

        $this->frontendHelper->expects(self::any())
            ->method('isFrontendRequest')
            ->willReturn($frontendRequest);

        self::assertEquals($expected, $this->extension->isApplicable($configuration));
    }

    public function isApplicableDataProvider(): array
    {
        return [
            'Applicable' => [
                'gridConfig' => ['name' => 'frontend-quotes-grid'],
                'frontendRequest' => true,
                'token' => self::createMock(AnonymousCustomerUserToken::class),
                'expected' => true,
            ],
            'No Frontend Requests' => [
                'gridConfig' => ['name' => 'frontend-quotes-grid'],
                'frontendRequest' => false,
                'token' => self::createMock(AnonymousCustomerUserToken::class),
                'expected' => false,
            ],
            'Invalid token' => [
                'gridConfig' => ['name' => 'frontend-quotes-grid'],
                'frontendRequest' => false,
                'token' => self::createMock(TokenInterface::class),
                'expected' => false,
            ],
        ];
    }
}

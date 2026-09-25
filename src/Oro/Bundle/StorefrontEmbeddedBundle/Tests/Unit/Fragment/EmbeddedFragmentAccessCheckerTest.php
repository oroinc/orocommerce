<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Fragment;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend\EmbeddedThemeAssetsController;
use Oro\Bundle\StorefrontEmbeddedBundle\Fragment\EmbeddedFragmentAccessChecker;
use Oro\Bundle\StorefrontEmbeddedBundle\Fragment\EmbeddedFragmentTarget;
use Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Stub\CsrfProtectedActionStub;
use Oro\Bundle\UIBundle\Provider\ControllerClassProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class EmbeddedFragmentAccessCheckerTest extends TestCase
{
    private const string BLOCK = 'embedded_profile';
    private const string ROUTE_NAME = 'oro_customer_frontend_customer_user_profile';

    private FeatureChecker&MockObject $featureChecker;

    private ControllerClassProvider&MockObject $controllerClassProvider;

    private ClassAuthorizationChecker&MockObject $classAuthorizationChecker;

    private CsrfRequestManager&MockObject $csrfRequestManager;

    private EmbeddedFragmentAccessChecker $accessChecker;

    #[\Override]
    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->controllerClassProvider = $this->createMock(ControllerClassProvider::class);
        $this->classAuthorizationChecker = $this->createMock(ClassAuthorizationChecker::class);
        $this->csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $this->accessChecker = new EmbeddedFragmentAccessChecker(
            $this->featureChecker,
            $this->controllerClassProvider,
            $this->classAuthorizationChecker,
            $this->csrfRequestManager
        );
    }

    public function testDenyAccessUnlessGrantedThrowsNotFoundWhenTheTargetPageIsDisabled(): void
    {
        $this->featureChecker->method('isResourceEnabled')
            ->with(self::ROUTE_NAME, 'routes')
            ->willReturn(false);
        $this->controllerClassProvider->expects(self::never())
            ->method('getControllers');

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('The page behind embedded block "embedded_profile" is disabled.');

        $this->accessChecker->denyAccessUnlessGranted($this->createTarget(), Request::create('/', 'GET'));
    }

    public function testDenyAccessUnlessGrantedThrowsNotFoundWhenTheControllerIsUnknown(): void
    {
        $this->featureChecker->method('isResourceEnabled')
            ->willReturn(true);
        $this->controllerClassProvider->method('getControllers')
            ->willReturn([]);
        $this->classAuthorizationChecker->expects(self::never())
            ->method('isClassMethodGranted');

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Cannot resolve the controller behind embedded block "embedded_profile".');

        $this->accessChecker->denyAccessUnlessGranted($this->createTarget(), Request::create('/', 'GET'));
    }

    public function testDenyAccessUnlessGrantedThrowsAccessDeniedWhenTheAclDeniesAccess(): void
    {
        $this->featureChecker->method('isResourceEnabled')
            ->willReturn(true);
        $this->controllerClassProvider->method('getControllers')
            ->willReturn([self::ROUTE_NAME => [CsrfProtectedActionStub::class, 'saveAction']]);
        $this->classAuthorizationChecker->method('isClassMethodGranted')
            ->with(CsrfProtectedActionStub::class, 'saveAction')
            ->willReturn(false);
        $this->csrfRequestManager->expects(self::never())
            ->method('isRequestTokenValid');

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access denied to the page behind embedded block "embedded_profile".');

        $this->accessChecker->denyAccessUnlessGranted($this->createTarget(), Request::create('/', 'POST'));
    }

    public function testDenyAccessUnlessGrantedNeverChecksCsrfOnAGetRequest(): void
    {
        $this->featureChecker->method('isResourceEnabled')
            ->willReturn(true);
        $this->controllerClassProvider->method('getControllers')
            ->willReturn([self::ROUTE_NAME => [CsrfProtectedActionStub::class, 'saveAction']]);
        $this->classAuthorizationChecker->method('isClassMethodGranted')
            ->willReturn(true);
        $this->csrfRequestManager->expects(self::never())
            ->method('isRequestTokenValid');

        $this->accessChecker->denyAccessUnlessGranted($this->createTarget(), Request::create('/', 'GET'));
    }

    public function testDenyAccessUnlessGrantedPassesOnPostWhenTheTargetHasNoCsrfProtectionAttribute(): void
    {
        $this->featureChecker->method('isResourceEnabled')
            ->willReturn(true);
        $this->controllerClassProvider->method('getControllers')
            ->willReturn([self::ROUTE_NAME => [EmbeddedThemeAssetsController::class, 'themeAssetsAction']]);
        $this->classAuthorizationChecker->method('isClassMethodGranted')
            ->willReturn(true);
        $this->csrfRequestManager->expects(self::never())
            ->method('isRequestTokenValid');

        $this->accessChecker->denyAccessUnlessGranted($this->createTarget(), Request::create('/', 'POST'));
    }

    public function testDenyAccessUnlessGrantedThrowsAccessDeniedHttpExceptionWhenTheCsrfTokenIsInvalid(): void
    {
        $this->featureChecker->method('isResourceEnabled')
            ->willReturn(true);
        $this->controllerClassProvider->method('getControllers')
            ->willReturn([self::ROUTE_NAME => [CsrfProtectedActionStub::class, 'saveAction']]);
        $this->classAuthorizationChecker->method('isClassMethodGranted')
            ->willReturn(true);
        $this->csrfRequestManager->expects(self::once())
            ->method('isRequestTokenValid')
            ->willReturn(false);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Invalid CSRF token');

        $this->accessChecker->denyAccessUnlessGranted($this->createTarget(), Request::create('/', 'POST'));
    }

    public function testDenyAccessUnlessGrantedPassesOnPostWhenTheCsrfTokenIsValid(): void
    {
        $this->featureChecker->method('isResourceEnabled')
            ->willReturn(true);
        $this->controllerClassProvider->method('getControllers')
            ->willReturn([self::ROUTE_NAME => [CsrfProtectedActionStub::class, 'saveAction']]);
        $this->classAuthorizationChecker->method('isClassMethodGranted')
            ->willReturn(true);
        $this->csrfRequestManager->expects(self::once())
            ->method('isRequestTokenValid')
            ->willReturn(true);

        $this->accessChecker->denyAccessUnlessGranted($this->createTarget(), Request::create('/', 'POST'));
    }

    private function createTarget(): EmbeddedFragmentTarget
    {
        return new EmbeddedFragmentTarget(self::BLOCK, self::ROUTE_NAME, '/customer/profile', '/customer/profile', [], []);
    }
}

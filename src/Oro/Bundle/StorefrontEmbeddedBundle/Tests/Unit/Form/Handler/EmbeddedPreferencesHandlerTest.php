<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Form\Handler;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManagerInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\StorefrontEmbeddedBundle\Form\Handler\EmbeddedPreferencesHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

final class EmbeddedPreferencesHandlerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;

    private EntityManagerInterface&MockObject $entityManager;

    private UserLocalizationManagerInterface&MockObject $userLocalizationManager;

    private UserCurrencyManager&MockObject $userCurrencyManager;

    private FormInterface&MockObject $form;

    /**
     * @var array<string>
     */
    private array $calls = [];

    private EmbeddedPreferencesHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userLocalizationManager = $this->createMock(UserLocalizationManagerInterface::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->form = $this->createMock(FormInterface::class);

        $this->form

            ->expects(self::any())
            ->method('getName')
            ->willReturn('');
        $this->entityManager
            ->expects(self::never())
            ->method('flush');
        $this->entityManager
            ->expects(self::never())
            ->method('transactional');

        $this->handler = new EmbeddedPreferencesHandler(
            $this->doctrine,
            $this->userLocalizationManager,
            $this->userCurrencyManager
        );
    }

    public function testProcessStoresLocalizationAndCurrencyInOneTransaction(): void
    {
        $localization = new Localization();
        $this->form
            ->expects(self::once())
            ->method('setData')
            ->with(null);
        $this->form
            ->expects(self::once())
            ->method('submit')
            ->with(['localization' => '42', 'currency' => 'USD'], false)
            ->willReturnCallback(function (): FormInterface {
                $this->calls[] = 'submit';

                return $this->form;
            });
        $this->form
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);
        $this->form
            ->expects(self::once())
            ->method('getData')
            ->willReturn(['localization' => $localization, 'currency' => 'USD']);
        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(CustomerUser::class)
            ->willReturn($this->entityManager);
        $this->entityManager
            ->expects(self::once())
            ->method('beginTransaction')
            ->willReturnCallback(function (): void {
                $this->calls[] = 'beginTransaction';
            });
        $this->entityManager
            ->expects(self::once())
            ->method('commit')
            ->willReturnCallback(function (): void {
                $this->calls[] = 'commit';
            });
        $this->entityManager
            ->expects(self::never())
            ->method('rollback');
        $this->userLocalizationManager
            ->expects(self::once())
            ->method('setCurrentLocalization')
            ->with(self::identicalTo($localization))
            ->willReturnCallback(function (): void {
                $this->calls[] = 'setCurrentLocalization';
            });
        $this->userCurrencyManager
            ->expects(self::once())
            ->method('saveSelectedCurrency')
            ->with('USD')
            ->willReturnCallback(function (): void {
                $this->calls[] = 'saveSelectedCurrency';
            });

        self::assertTrue(
            $this->handler->process(
                null,
                $this->form,
                new Request([], ['localization' => '42', 'currency' => 'USD'])
            )
        );
        self::assertSame(
            ['submit', 'beginTransaction', 'setCurrentLocalization', 'saveSelectedCurrency', 'commit'],
            $this->calls
        );
    }

    public function testProcessStoresOnlyLocalizationWhenNoCurrencyIsGiven(): void
    {
        $localization = new Localization();
        $this->form
            ->expects(self::once())
            ->method('submit')
            ->with(['localization' => '42'], false);
        $this->form
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);
        $this->form
            ->expects(self::once())
            ->method('getData')
            ->willReturn(['localization' => $localization]);
        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(CustomerUser::class)
            ->willReturn($this->entityManager);
        $this->entityManager
            ->expects(self::once())
            ->method('beginTransaction')
            ->willReturnCallback(function (): void {
                $this->calls[] = 'beginTransaction';
            });
        $this->entityManager
            ->expects(self::once())
            ->method('commit')
            ->willReturnCallback(function (): void {
                $this->calls[] = 'commit';
            });
        $this->entityManager
            ->expects(self::never())
            ->method('rollback');
        $this->userLocalizationManager
            ->expects(self::once())
            ->method('setCurrentLocalization')
            ->with(self::identicalTo($localization))
            ->willReturnCallback(function (): void {
                $this->calls[] = 'setCurrentLocalization';
            });
        $this->userCurrencyManager
            ->expects(self::never())
            ->method('saveSelectedCurrency');

        self::assertTrue(
            $this->handler->process(null, $this->form, new Request([], ['localization' => '42']))
        );
        self::assertSame(['beginTransaction', 'setCurrentLocalization', 'commit'], $this->calls);
    }

    public function testProcessStoresOnlyCurrencyWhenNoLocalizationIsGiven(): void
    {
        $this->form
            ->expects(self::once())
            ->method('submit')
            ->with(['currency' => 'USD'], false);
        $this->form
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);
        $this->form
            ->expects(self::once())
            ->method('getData')
            ->willReturn(['currency' => 'USD']);
        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(CustomerUser::class)
            ->willReturn($this->entityManager);
        $this->entityManager
            ->expects(self::once())
            ->method('beginTransaction')
            ->willReturnCallback(function (): void {
                $this->calls[] = 'beginTransaction';
            });
        $this->entityManager
            ->expects(self::once())
            ->method('commit')
            ->willReturnCallback(function (): void {
                $this->calls[] = 'commit';
            });
        $this->entityManager
            ->expects(self::never())
            ->method('rollback');
        $this->userLocalizationManager
            ->expects(self::never())
            ->method('setCurrentLocalization');
        $this->userCurrencyManager
            ->expects(self::once())
            ->method('saveSelectedCurrency')
            ->with('USD')
            ->willReturnCallback(function (): void {
                $this->calls[] = 'saveSelectedCurrency';
            });

        self::assertTrue(
            $this->handler->process(null, $this->form, new Request([], ['currency' => 'USD']))
        );
        self::assertSame(['beginTransaction', 'saveSelectedCurrency', 'commit'], $this->calls);
    }

    public function testProcessReturnsFalseAndStoresNothingWhenTheFormIsNotValid(): void
    {
        $this->form
            ->expects(self::once())
            ->method('submit')
            ->with(['currency' => 'XYZ'], false);
        $this->form
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(false);
        $this->form
            ->expects(self::never())
            ->method('getData');
        $this->doctrine
            ->expects(self::never())
            ->method('getManagerForClass');
        $this->userLocalizationManager
            ->expects(self::never())
            ->method('setCurrentLocalization');
        $this->userCurrencyManager
            ->expects(self::never())
            ->method('saveSelectedCurrency');

        self::assertFalse(
            $this->handler->process(null, $this->form, new Request([], ['currency' => 'XYZ']))
        );
        self::assertSame([], $this->calls);
    }

    public function testProcessRollsBackAndRethrowsWhenStoringLocalizationFails(): void
    {
        $this->form
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);
        $this->form
            ->expects(self::once())
            ->method('getData')
            ->willReturn(['localization' => new Localization(), 'currency' => 'USD']);
        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(CustomerUser::class)
            ->willReturn($this->entityManager);
        $this->entityManager
            ->expects(self::once())
            ->method('beginTransaction');
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('isTransactionActive')
            ->willReturn(true);
        $this->entityManager
            ->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);
        $this->entityManager
            ->expects(self::once())
            ->method('rollback');
        $this->entityManager
            ->expects(self::never())
            ->method('commit');
        $exception = new \RuntimeException('Localization is not stored.');
        $this->userLocalizationManager
            ->expects(self::once())
            ->method('setCurrentLocalization')
            ->willThrowException($exception);
        $this->userCurrencyManager
            ->expects(self::never())
            ->method('saveSelectedCurrency');

        $this->expectExceptionObject($exception);

        $this->handler->process(null, $this->form, new Request([], ['localization' => '42', 'currency' => 'USD']));
    }

    public function testProcessRollsBackAndRethrowsWhenStoringCurrencyFails(): void
    {
        $this->form
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);
        $this->form
            ->expects(self::once())
            ->method('getData')
            ->willReturn(['localization' => new Localization(), 'currency' => 'USD']);
        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(CustomerUser::class)
            ->willReturn($this->entityManager);
        $this->entityManager
            ->expects(self::once())
            ->method('beginTransaction');
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('isTransactionActive')
            ->willReturn(true);
        $this->entityManager
            ->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);
        $this->entityManager
            ->expects(self::once())
            ->method('rollback');
        $this->entityManager
            ->expects(self::never())
            ->method('commit');
        $this->userLocalizationManager
            ->expects(self::once())
            ->method('setCurrentLocalization');
        $this->userCurrencyManager
            ->expects(self::once())
            ->method('saveSelectedCurrency')
            ->willThrowException(new \Error('Currency is not stored.'));

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Currency is not stored.');

        $this->handler->process(null, $this->form, new Request([], ['localization' => '42', 'currency' => 'USD']));
    }

    public function testProcessRethrowsCommitFailureWithoutRollbackWhenTheTransactionIsNotActiveAnyMore(): void
    {
        $this->form
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);
        $this->form
            ->expects(self::once())
            ->method('getData')
            ->willReturn(['currency' => 'USD']);
        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(CustomerUser::class)
            ->willReturn($this->entityManager);
        $this->entityManager
            ->expects(self::once())
            ->method('beginTransaction');
        $exception = new \RuntimeException('The transaction is not committed.');
        $this->entityManager
            ->expects(self::once())
            ->method('commit')
            ->willThrowException($exception);
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('isTransactionActive')
            ->willReturn(false);
        $this->entityManager
            ->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);
        $this->entityManager
            ->expects(self::never())
            ->method('rollback');

        $this->expectExceptionObject($exception);

        $this->handler->process(null, $this->form, new Request([], ['currency' => 'USD']));
    }
}

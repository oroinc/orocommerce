<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Functional\Form\Type;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\WebsiteManagerTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadDisabledLocalizationData;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\StorefrontEmbeddedBundle\Form\Type\EmbeddedPreferencesType;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Tests the embedded preferences form type with the real localization and currency managers
 * for the default website, where the default localization and the "en_CA" localization are enabled.
 * The test environment has only one currency, so the valid currency is the default one.
 *
 * @dbIsolationPerTest
 */
final class EmbeddedPreferencesTypeTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;
    use FormAwareTestTrait;
    use WebsiteManagerTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadLocalizationData::class,
            LoadDisabledLocalizationData::class
        ]);
        $this->setCurrentWebsite();

        $this->setConfigValue(
            'oro_locale.enabled_localizations',
            [
                $this->getReference(LoadLocalizationData::DEFAULT_LOCALIZATION_CODE)->getId(),
                $this->getReference(LoadLocalizationData::EN_CA_LOCALIZATION_CODE)->getId()
            ]
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->restoreConfigValues();
    }

    /**
     * @beforeResetClient
     */
    public static function afterFrontendTest(): void
    {
        self::getWebsiteManagerStub()->disableStub();
    }

    public function testBuildsLocalizationAndCurrencyFields(): void
    {
        $currency = self::getContainer()->get('oro_pricing.user_currency_manager')->getDefaultCurrency();

        $form = self::createForm(EmbeddedPreferencesType::class);

        self::assertFormOptions($form, ['data_class' => null]);
        self::assertFormHasField($form, 'localization', EntityType::class, [
            'class' => Localization::class,
            'constraints' => [new NotBlank()]
        ]);
        self::assertFormHasField($form, 'currency', ChoiceType::class, [
            'choices' => [$currency => $currency],
            'constraints' => [new NotBlank()]
        ]);
    }

    public function testSubmitOfEnabledLocalizationGivesManagedLocalization(): void
    {
        $localizationId = $this->getReference(LoadLocalizationData::EN_CA_LOCALIZATION_CODE)->getId();

        $form = self::createForm(EmbeddedPreferencesType::class);
        $form->submit(['localization' => (string)$localizationId], false);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertSame(
            [
                'localization' => self::getContainer()->get('doctrine')->getManagerForClass(Localization::class)
                    ->find(Localization::class, $localizationId)
            ],
            $form->getData()
        );
    }

    public function testSubmitOfLocalizationEnabledForTheWebsiteButNotGloballyGivesManagedLocalization(): void
    {
        $this->markTestSkipped('Should be unskipped once the bundle moves to separate package');

        $defaultLocalizationId = $this->getReference(LoadLocalizationData::DEFAULT_LOCALIZATION_CODE)->getId();
        $localizationId = $this->getReference(LoadLocalizationData::EN_CA_LOCALIZATION_CODE)->getId();
        $this->setConfigValue(
            'oro_locale.enabled_localizations',
            [$defaultLocalizationId, $localizationId],
            'website'
        );
        $this->setConfigValue('oro_locale.enabled_localizations', [$defaultLocalizationId]);

        $form = self::createForm(EmbeddedPreferencesType::class);
        $form->submit(['localization' => (string)$localizationId], false);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertSame(
            [
                'localization' => self::getContainer()->get('doctrine')->getManagerForClass(Localization::class)
                    ->find(Localization::class, $localizationId)
            ],
            $form->getData()
        );
    }

    public function testSubmitOfAvailableCurrencyGivesCurrencyCode(): void
    {
        $currency = self::getContainer()->get('oro_pricing.user_currency_manager')->getDefaultCurrency();

        $form = self::createForm(EmbeddedPreferencesType::class);
        $form->submit(['currency' => $currency], false);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertSame(['currency' => $currency], $form->getData());
    }

    public function testSubmitOfEnabledLocalizationAndAvailableCurrencyGivesBothValues(): void
    {
        $localizationId = $this->getReference(LoadLocalizationData::EN_CA_LOCALIZATION_CODE)->getId();
        $currency = self::getContainer()->get('oro_pricing.user_currency_manager')->getDefaultCurrency();

        $form = self::createForm(EmbeddedPreferencesType::class);
        $form->submit(['localization' => (string)$localizationId, 'currency' => $currency], false);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertSame(
            [
                'localization' => self::getContainer()->get('doctrine')
                    ->getManagerForClass(Localization::class)
                    ->find(Localization::class, $localizationId),
                'currency' => $currency
            ],
            $form->getData()
        );
    }

    public function testSubmitWithoutAnyValueIsInvalid(): void
    {
        $form = self::createForm(EmbeddedPreferencesType::class);
        $form->submit([], false);

        self::assertFalse($form->isValid());
        self::assertCount(1, $form->getErrors());
        self::assertSame(
            'oro.storefront_embedded.embedded_preferences.empty.message',
            $form->getErrors()[0]->getMessageTemplate()
        );
    }

    public function testSubmitWithUnknownExtraFieldOnlyIsInvalid(): void
    {
        $form = self::createForm(EmbeddedPreferencesType::class);
        $form->submit(['theme' => 'dark'], false);

        self::assertFalse($form->isValid());
        self::assertCount(2, $form->getErrors());
        self::assertSame(
            'oro.storefront_embedded.embedded_preferences.empty.message',
            $form->getErrors()[0]->getMessageTemplate()
        );
        self::assertSame(
            'oro.form.extra_fields',
            $form->getErrors()[1]->getMessageTemplate()
        );
    }

    /**
     * The "%d" placeholder of the template is replaced with the id of the enabled "en_CA" localization.
     *
     * @dataProvider submitOfRefusedLocalizationIsInvalidProvider
     */
    public function testSubmitOfRefusedLocalizationIsInvalid(
        string $localizationIdTemplate,
        string $expectedMessageTemplate
    ): void {
        $form = self::createForm(EmbeddedPreferencesType::class);
        $form->submit(
            [
                'localization' => sprintf(
                    $localizationIdTemplate,
                    $this->getReference(LoadLocalizationData::EN_CA_LOCALIZATION_CODE)->getId()
                )
            ],
            false
        );

        self::assertFalse($form->isValid());
        self::assertCount(1, $form->get('localization')->getErrors());
        self::assertSame(
            $expectedMessageTemplate,
            $form->get('localization')->getErrors()[0]->getMessageTemplate()
        );
    }

    public static function submitOfRefusedLocalizationIsInvalidProvider(): iterable
    {
        yield 'localization id that does not exist' => [(string)self::BIGINT, 'The selected choice is invalid.'];
        yield 'not a number' => ['not-a-number', 'The selected choice is invalid.'];
        yield 'empty string' => ['', 'This value should not be blank.'];
        yield 'id surrounded by spaces' => [' %d ', 'The selected choice is invalid.'];
    }

    public function testSubmitOfLocalizationThatIsNotEnabledIsInvalid(): void
    {
        $form = self::createForm(EmbeddedPreferencesType::class);
        $form->submit(['localization' => (string)$this->getReference('es_MX')->getId()], false);

        self::assertFalse($form->isValid());
        self::assertCount(1, $form->get('localization')->getErrors());
        self::assertSame(
            'The selected choice is invalid.',
            $form->get('localization')->getErrors()[0]->getMessageTemplate()
        );
    }

    public function testSubmitOfLocalizationEnabledGloballyButNotForTheWebsiteIsInvalid(): void
    {
        $this->markTestSkipped('Should be unskipped once the bundle moves to separate package');

        $this->setConfigValue(
            'oro_locale.enabled_localizations',
            [$this->getReference(LoadLocalizationData::DEFAULT_LOCALIZATION_CODE)->getId()],
            'website'
        );

        $form = self::createForm(EmbeddedPreferencesType::class);
        $form->submit(
            ['localization' => (string)$this->getReference(LoadLocalizationData::EN_CA_LOCALIZATION_CODE)->getId()],
            false
        );

        self::assertFalse($form->isValid());
        self::assertCount(1, $form->get('localization')->getErrors());
        self::assertSame(
            'The selected choice is invalid.',
            $form->get('localization')->getErrors()[0]->getMessageTemplate()
        );
    }

    public function testSubmitOfLocalizationGivenAsArrayIsInvalid(): void
    {
        $form = self::createForm(EmbeddedPreferencesType::class);
        $form->submit(
            ['localization' => [(string)$this->getReference(LoadLocalizationData::EN_CA_LOCALIZATION_CODE)->getId()]],
            false
        );

        self::assertFalse($form->isValid());
        self::assertCount(1, $form->get('localization')->getErrors());
        self::assertSame(
            'The selected choice is invalid.',
            $form->get('localization')->getErrors()[0]->getMessageTemplate()
        );
    }

    /**
     * @dataProvider submitOfRefusedCurrencyIsInvalidProvider
     */
    public function testSubmitOfRefusedCurrencyIsInvalid(string $currency, string $expectedMessageTemplate): void
    {
        $form = self::createForm(EmbeddedPreferencesType::class);
        $form->submit(['currency' => $currency], false);

        self::assertFalse($form->isValid());
        self::assertCount(1, $form->get('currency')->getErrors());
        self::assertSame(
            $expectedMessageTemplate,
            $form->get('currency')->getErrors()[0]->getMessageTemplate()
        );
    }

    public static function submitOfRefusedCurrencyIsInvalidProvider(): iterable
    {
        yield 'unknown currency' => ['XYZ', 'The selected choice is invalid.'];
        yield 'empty string' => ['', 'This value should not be blank.'];
    }

    public function testSubmitOfCurrencyCodeInLowerCaseIsInvalid(): void
    {
        $form = self::createForm(EmbeddedPreferencesType::class);
        $form->submit(
            [
                'currency' => strtolower(
                    self::getContainer()->get('oro_pricing.user_currency_manager')->getDefaultCurrency()
                )
            ],
            false
        );

        self::assertFalse($form->isValid());
        self::assertCount(1, $form->get('currency')->getErrors());
        self::assertSame(
            'The selected choice is invalid.',
            $form->get('currency')->getErrors()[0]->getMessageTemplate()
        );
    }

    public function testSubmitOfCurrencyGivenAsArrayIsInvalid(): void
    {
        $form = self::createForm(EmbeddedPreferencesType::class);
        $form->submit(
            ['currency' => [self::getContainer()->get('oro_pricing.user_currency_manager')->getDefaultCurrency()]],
            false
        );

        self::assertFalse($form->isValid());
        self::assertCount(1, $form->get('currency')->getErrors());
        self::assertSame(
            'The selected choice is invalid.',
            $form->get('currency')->getErrors()[0]->getMessageTemplate()
        );
    }

    public function testSubmitOfEnabledLocalizationWithRefusedCurrencyIsInvalid(): void
    {
        $form = self::createForm(EmbeddedPreferencesType::class);
        $form->submit(
            [
                'localization' => (string)$this->getReference(LoadLocalizationData::EN_CA_LOCALIZATION_CODE)->getId(),
                'currency' => 'XYZ'
            ],
            false
        );

        self::assertFalse($form->isValid());
        self::assertTrue($form->get('localization')->isValid());
        self::assertCount(1, $form->get('currency')->getErrors());
        self::assertSame(
            'The selected choice is invalid.',
            $form->get('currency')->getErrors()[0]->getMessageTemplate()
        );
    }
}

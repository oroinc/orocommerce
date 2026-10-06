<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManagerInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Validates the localization and the currency of an embedded preferences request.
 * The data is an array: ['localization' => ?Localization, 'currency' => ?string].
 */
final class EmbeddedPreferencesType extends AbstractType
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly UserLocalizationManagerInterface $userLocalizationManager,
        private readonly UserCurrencyManager $userCurrencyManager
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('localization', EntityType::class, [
                'class' => Localization::class,
                'choice_loader' => new CallbackChoiceLoader($this->loadEnabledLocalizations(...)),
                'constraints' => [new NotBlank()],
            ])
            ->add('currency', ChoiceType::class, [
                'choices' => $this->getAvailableCurrencyChoices(),
                'constraints' => [new NotBlank()],
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'constraints' => [new Callback($this->validateAtLeastOneValue(...))],
        ]);
    }

    /**
     * @return array<Localization>
     */
    private function loadEnabledLocalizations(): array
    {
        $localizationIds = array_keys($this->userLocalizationManager->getEnabledLocalizations());
        if (!$localizationIds) {
            return [];
        }

        return $this->doctrine->getRepository(Localization::class)->findBy(['id' => $localizationIds]);
    }

    /**
     * @return array<string, string>
     */
    private function getAvailableCurrencyChoices(): array
    {
        $currencies = $this->userCurrencyManager->getAvailableCurrencies();

        return array_combine($currencies, $currencies);
    }

    /**
     * @param array{localization?: ?Localization, currency?: ?string}|null $data
     */
    private function validateAtLeastOneValue(?array $data, ExecutionContextInterface $context): void
    {
        if (null === ($data['localization'] ?? null) && null === ($data['currency'] ?? null)) {
            $context->buildViolation('oro.storefront_embedded.embedded_preferences.empty.message')
                ->addViolation();
        }
    }
}

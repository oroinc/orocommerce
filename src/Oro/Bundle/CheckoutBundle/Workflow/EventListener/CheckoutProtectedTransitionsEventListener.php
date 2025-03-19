<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\EventListener;

use Oro\Bundle\CheckoutBundle\DependencyInjection\Compiler\AddCheckoutStartToCaptchaProtected;
use Oro\Bundle\FormBundle\Captcha\CaptchaServiceRegistry;
use Oro\Bundle\FormBundle\Captcha\CaptchaSettingsProvider;
use Oro\Bundle\FormBundle\Form\Type\CaptchaType;
use Oro\Bundle\FormBundle\Form\Type\ReCaptchaType;
use Oro\Bundle\WorkflowBundle\Event\Transition\PreGuardEvent;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionAssembleEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Add CAPTCHA to protected checkout transition form.
 */
class CheckoutProtectedTransitionsEventListener
{
    public function __construct(
        private CaptchaSettingsProvider $captchaSettingsProvider,
        private CaptchaServiceRegistry $captchaServiceRegistry,
        private TranslatorInterface $translator
    ) {
    }

    public function onPreGuard(PreGuardEvent $event): void
    {
        if (!$event->isAllowed()) {
            return;
        }

        $transition = $event->getTransition();
        if (empty($transition->getFrontendOptions()['is_captcha_protected'])) {
            return;
        }

        if (!$this->isCaptchaProtectionApplicable()) {
            return;
        }

        $workflowItem = $event->getWorkflowItem();
        $data = $workflowItem->getData();
        $result = $workflowItem->getResult();
        // Check CAPTCHA token only once
        if (!empty($result->offsetGet('is_captcha_checked'))) {
            return;
        }

        $captchaToken = $data->offsetGet('captcha');
        if ($this->captchaServiceRegistry->getCaptchaService()->isVerified($captchaToken)) {
            $result->offsetSet('is_captcha_checked', true);
        } else {
            $event->getErrors()?->add([
                'message' => 'oro.checkout.validator.start_transition.captcha_not_verified.message'
            ]);
            $event->setAllowed(false);
        }
        // CAPTCHA token may be verified only once, no need to store it
        $data->offsetUnset('captcha');
    }

    public function onAssemble(TransitionAssembleEvent $event): void
    {
        if (!$this->isCaptchaProtectionApplicable()) {
            return;
        }

        if (!$this->isSupportedTransition($event)) {
            return;
        }

        $options = $event->getOptions();
        if (!array_key_exists('form_options', $options)) {
            $options['form_options'] = ['attribute_fields' => []];
        }

        $options['form_options']['attribute_fields']['captcha'] = [
            'form_type' => CaptchaType::class,
            'options' => [
                'constraints' => []
            ]
        ];

        $options['frontend_options']['data']['dialog-title'] = $this->translator->trans(
            'oro.workflow.checkout.captcha.verification_dialog.title',
            [],
            'workflows'
        );

        if ($this->captchaSettingsProvider->getFormType() === ReCaptchaType::class) {
            $options['message'] = 'oro.workflow.checkout.captcha.recaptcha.warning.message';
        } else {
            $options['message'] = 'oro.workflow.checkout.captcha.warning.message';
        }

        $event->setOptions($options);
    }

    private function isSupportedTransition(TransitionAssembleEvent $event): bool
    {
        $options = $event->getOptions();

        return !empty($options['frontend_options']['is_captcha_protected'])
            && empty($options['form_options']['attribute_fields']['captcha']);
    }

    private function isCaptchaProtectionApplicable(): bool
    {
        if (!$this->captchaSettingsProvider->isProtectionAvailable()) {
            return false;
        }

        if (!$this->captchaSettingsProvider->isFormProtected(AddCheckoutStartToCaptchaProtected::PROTECTION_KEY)) {
            return false;
        }

        return true;
    }
}

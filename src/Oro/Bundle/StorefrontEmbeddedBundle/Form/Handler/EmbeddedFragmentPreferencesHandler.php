<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Form\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManagerInterface;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Stores the localization and the currency of the current embedded session.
 */
class EmbeddedFragmentPreferencesHandler implements FormHandlerInterface
{
    use RequestHandlerTrait;

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly UserLocalizationManagerInterface $userLocalizationManager,
        private readonly UserCurrencyManager $userCurrencyManager
    ) {
    }

    #[\Override]
    public function process($data, FormInterface $form, Request $request): bool
    {
        $form->setData($data);
        // A partial submit: an absent field is not submitted, so it is neither validated nor stored.
        $this->submitPostPutRequest($form, $request, false);
        if (!$form->isValid()) {
            return false;
        }

        $preferences = $form->getData();
        $localization = $preferences['localization'] ?? null;
        $currency = $preferences['currency'] ?? null;

        $entityManager = $this->doctrine->getManagerForClass(CustomerUser::class);
        $entityManager->beginTransaction();
        try {
            if (null !== $localization) {
                $this->userLocalizationManager->setCurrentLocalization($localization);
            }
            if (null !== $currency) {
                $this->userCurrencyManager->saveSelectedCurrency($currency);
            }
            $entityManager->commit();
        } catch (\Throwable $exception) {
            if ($entityManager->getConnection()->isTransactionActive()) {
                $entityManager->rollback();
            }

            throw $exception;
        }

        return true;
    }
}

<?php

namespace Oro\Bundle\SaleBundle\Workflow\Operation;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\AbstractOperationService;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\OperationServiceInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Manager\QuoteDemandManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * oro_sale_frontend_guest_quote_submit_to_order operation logic.
 */
class GuestQuoteSubmitToOrder extends AbstractOperationService
{
    public function __construct(
        private OperationServiceInterface $baseQuoteSubmitToOrder,
        private FeatureChecker $featureChecker,
        private ManagerRegistry $registry,
        private QuoteDemandManager $quoteDemandManager,
        private TokenStorageInterface $tokenStorage,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function isPreConditionAllowed(ActionData $data, ?Collection $errors = null): bool
    {
        if (!$this->baseQuoteSubmitToOrder->isPreConditionAllowed($data, $errors)) {
            return false;
        }

        $token = $this->tokenStorage->getToken();
        if (!$token instanceof AnonymousCustomerUserToken) {
            return true;
        }

        return $this->featureChecker->isFeatureEnabled('guest_checkout');
    }

    public function execute(ActionData $data): void
    {
        $token = $this->tokenStorage->getToken();
        if (!$token instanceof AnonymousCustomerUserToken) {
            $this->baseQuoteSubmitToOrder->execute($data);

            return;
        }

        /** @var Quote $quote */
        $quote = $data->getEntity();
        $visitor = $token->getVisitor();
        $quoteDemand = $this->registry->getRepository(QuoteDemand::class)
            ->findOneBy(['quote' => $quote, 'visitor' => $visitor]);

        if (!$quoteDemand) {
            $quoteDemand = $this->createNewQuoteDemand($quote, $visitor);
        }

        $data->offsetSet(
            'redirectUrl',
            $this->urlGenerator->generate('oro_sale_quote_frontend_choice', ['id' => $quoteDemand->getId()])
        );
    }

    private function createNewQuoteDemand(
        Quote $quote,
        CustomerVisitor $visitor
    ): QuoteDemand {
        $em = $this->registry->getManagerForClass(QuoteDemand::class);
        $quoteDemand = new QuoteDemand();
        $quoteDemand->setQuote($quote);
        $quoteDemand->setVisitor($visitor);

        $this->quoteDemandManager->recalculateSubtotals($quoteDemand);
        $this->quoteDemandManager->updateQuoteProductDemandChecksum($quoteDemand);

        $em->persist($quoteDemand);
        $em->flush($quoteDemand);

        return $quoteDemand;
    }
}

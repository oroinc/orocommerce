<?php

namespace Oro\Bundle\SaleBundle\Workflow\Operation;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\AbstractOperationService;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Manager\QuoteDemandManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * oro_sale_frontend_quote_submit_to_order operation logic.
 */
class QuoteSubmitToOrder extends AbstractOperationService
{
    public function __construct(
        private WorkflowManager $workflowManager,
        private ActionExecutor $actionExecutor,
        private ManagerRegistry $registry,
        private QuoteDemandManager $quoteDemandManager,
        private TokenStorageInterface $tokenStorage,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function isPreConditionAllowed(ActionData $data, Collection $errors = null): bool
    {
        $quoteAcceptable = $this->actionExecutor->evaluateExpression(
            'quote_acceptable',
            [$data->getEntity()],
            $errors
        );
        if (!$quoteAcceptable) {
            return false;
        }

        $workflow = $this->workflowManager->getAvailableWorkflowByRecordGroup(Checkout::class, 'b2b_checkout_flow');
        if (!$workflow) {
            return false;
        }

        return true;
    }

    public function execute(ActionData $data): void
    {
        /** @var Quote $quote */
        $quote = $data->getEntity();
        $currentUser = $this->tokenStorage->getToken()?->getUser();
        $quoteDemand = $this->registry->getRepository(QuoteDemand::class)
            ->findOneBy(['quote' => $quote, 'customerUser' => $currentUser]);

        if (!$quoteDemand) {
            $quoteDemand = $this->createNewQuoteDemand($quote, $currentUser);
        }

        $data->offsetSet(
            'redirectUrl',
            $this->urlGenerator->generate('oro_sale_quote_frontend_choice', ['id' => $quoteDemand->getId()])
        );
    }

    private function createNewQuoteDemand(
        Quote $quote,
        ?CustomerUser $currentUser
    ): QuoteDemand {
        $em = $this->registry->getManagerForClass(QuoteDemand::class);
        $quoteDemand = new QuoteDemand();
        $quoteDemand->setQuote($quote);
        $quoteDemand->setCustomerUser($currentUser);

        $this->quoteDemandManager->recalculateSubtotals($quoteDemand);
        $this->quoteDemandManager->updateQuoteProductDemandChecksum($quoteDemand);

        $em->persist($quoteDemand);
        $em->flush($quoteDemand);

        return $quoteDemand;
    }
}

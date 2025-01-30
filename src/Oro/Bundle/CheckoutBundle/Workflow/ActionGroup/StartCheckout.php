<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\PrepareCheckoutSettingsProvider;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Component\Action\Condition\ExtendableCondition;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Helper logic to start the Checkout workflow.
 */
class StartCheckout implements StartCheckoutInterface
{
    public function __construct(
        private PrepareCheckoutSettingsInterface $prepareCheckoutSettings,
        private FindOrCreateCheckoutInterface $findOrCreateCheckout,
        private PrepareCheckoutSettingsProvider $prepareCheckoutSettingsProvider,
        private UpdateWorkflowItemInterface $updateWorkflowItem,
        private TokenStorageInterface $tokenStorage,
        private UrlGeneratorInterface $urlGenerator,
        private ActionExecutor $actionExecutor
    ) {
    }

    public function execute(
        array $sourceCriteria,
        bool $force = false,
        array $data = [],
        array $settings = [],
        bool $showErrors = false,
        bool $forceStartCheckout = false,
        string $startTransition = null,
        bool $validateOnStartCheckout = true
    ): array {
        $findResult = $this->findOrCreateCheckout->execute(
            $sourceCriteria,
            $data,
            $force,
            $forceStartCheckout,
            $startTransition
        );

        /** @var Checkout $checkout */
        $checkout = $findResult['checkout'];
        /** @var WorkflowItem $workflowItem */
        $workflowItem = $findResult['workflowItem'];
        $forceUpdateSettings = !empty($findResult['updateData']);
        $sourceEntity = $checkout->getSourceEntity();

        if ($sourceEntity && $forceUpdateSettings) {
            $this->updateSettings($sourceEntity, $data, $settings, $checkout);
        }

        $this->updateRegisteredCustomerUser($checkout);

        $errors = new ArrayCollection([]);
        $result = ['checkout' => $checkout, 'workflowItem' => $workflowItem];
        if ($this->isStartAllowed($showErrors, $errors, $workflowItem, $validateOnStartCheckout)) {
            $result['redirectUrl'] = $this->urlGenerator->generate(
                'oro_checkout_frontend_checkout',
                ['id' => $checkout->getId()]
            );
        } else {
            $result['errors'] = $errors;
        }

        return $result;
    }

    private function getVisitor()
    {
        $token = $this->tokenStorage->getToken();
        if ($token instanceof AnonymousCustomerUserToken) {
            return $token->getVisitor();
        }

        return null;
    }

    private function updateSettings(
        CheckoutSourceEntityInterface $sourceEntity,
        array $data,
        array $settings,
        Checkout $checkout
    ): void {
        if (!empty($data['shippingAddress'])) {
            $settings['shipping_address'] = $data['shippingAddress'];
        }

        $preparedSettings = $this->prepareCheckoutSettingsProvider->prepareSettings(
            $checkout,
            $this->prepareCheckoutSettings->execute($sourceEntity)
        );
        $settings = ArrayUtil::arrayMergeRecursiveDistinct($settings, $preparedSettings);

        $this->updateWorkflowItem->execute($checkout, $settings);
    }

    private function updateRegisteredCustomerUser(Checkout $checkout): void
    {
        $visitor = $this->getVisitor();
        if (!$visitor) {
            return;
        }

        $checkout->setRegisteredCustomerUser(null);
        $this->actionExecutor->executeAction(
            'flush_entity',
            [$checkout]
        );
    }

    private function isStartAllowed(
        bool $showErrors,
        ArrayCollection $errors,
        WorkflowItem $workflowItem,
        bool $validateOnStartCheckout
    ): bool {
        $data = [
            'checkout' => $workflowItem->getEntity(),
            'validateOnStartCheckout' => $validateOnStartCheckout
        ];
        // BC layer
        $data[ExtendableConditionEvent::CONTEXT_KEY] = new ActionData($data);

        return $this->actionExecutor->evaluateExpression(
            expressionName: ExtendableCondition::NAME,
            data: [
                'events' => ['extendable_condition.start_checkout'],
                'showErrors' => $showErrors,
                'eventData' => $data
            ],
            errors: $errors
        );
    }
}

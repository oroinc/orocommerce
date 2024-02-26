<?php

namespace Oro\Bundle\SaleBundle\Controller;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Event\QuoteEvent;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType;
use Oro\Bundle\SaleBundle\Model\QuoteRequestHandler;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;

/**
 * Provides supportive actions for ajax calls during quote creation and editing.
 */
class AjaxQuoteController extends AbstractController
{
    /**
     * Get order related data
     *
     *
     * @return JsonResponse
     */
    #[Route(path: '/related-data', name: 'oro_quote_related_data', methods: ['GET'])]
    #[AclAncestor('oro_quote_update')]
    public function getRelatedDataAction()
    {
        $quote = new Quote();
        $customerUser = $this->getQuoteRequestHandler()->getCustomerUser();
        $customer = $this->getCustomer($customerUser);

        $quote->setCustomer($customer);
        $quote->setCustomerUser($customerUser);

        $customerPaymentTerm = null;
        $customerGroupPaymentTerm = null;

        if ($customer instanceof Customer) {
            $customerPaymentTerm = $this->getPaymentTermProvider()->getCustomerPaymentTerm($customer);

            if ($customer->getGroup() instanceof CustomerGroup) {
                $customerGroupPaymentTerm = $this->getPaymentTermProvider()
                    ->getCustomerGroupPaymentTerm($customer->getGroup());
            }
        }

        $quoteForm = $this->createForm($this->getQuoteFormTypeName(), $quote);

        $responseData = [
            'customerPaymentTerm' => $customerPaymentTerm?->getId(),
            'customerGroupPaymentTerm' => $customerGroupPaymentTerm?->getId(),
        ];
        if ($quoteForm->has(AddressType::TYPE_SHIPPING . 'Address')) {
            $responseData['shippingAddress'] = $this->renderFormView(
                $quoteForm->get(AddressType::TYPE_SHIPPING . 'Address')->createView()
            );
        }

        return new JsonResponse($responseData);
    }

    /**
     *
     * @param Request    $request
     * @param Quote|null $quote
     * @return JsonResponse
     */
    #[Route(path: '/entry-point/{id}', name: 'oro_quote_entry_point', defaults: ['id' => 0], methods: ['POST'])]
    #[AclAncestor('oro_quote_update')]
    #[CsrfProtection()]
    public function entryPointAction(Request $request, Quote $quote = null)
    {
        if (!$quote) {
            $quote = new Quote();
        }

        $form = $this->createForm(
            $this->getQuoteFormTypeName(),
            $quote,
            [
                'validation_groups' => $this->getValidationGroups($quote),
            ]
        );

        $submittedData = $request->get($form->getName());

        $form->submit($submittedData);

        $event = new QuoteEvent($form, $form->getData(), $submittedData);
        $this->container->get(EventDispatcherInterface::class)->dispatch($event, QuoteEvent::NAME);

        return new JsonResponse($event->getData());
    }

    protected function renderFormView(FormView $formView): string
    {
        return $this->renderView('@OroSale/Form/customerAddressSelector.html.twig', ['form' => $formView]);
    }

    /**
     * @param CustomerUser|null $customerUser
     * @return null|Customer
     */
    protected function getCustomer(CustomerUser $customerUser = null)
    {
        $customer = $this->getQuoteRequestHandler()->getCustomer();
        if (!$customer && $customerUser) {
            $customer = $customerUser->getCustomer();
        }
        if ($customer && $customerUser) {
            $this->validateRelation($customerUser, $customer);
        }

        return $customer;
    }

    /**
     * @throws BadRequestHttpException
     */
    protected function validateRelation(CustomerUser $customerUser, Customer $customer)
    {
        if ($customerUser->getCustomer() &&
            $customerUser->getCustomer()->getId() !== $customer->getId()
        ) {
            throw new BadRequestHttpException('CustomerUser must belong to Customer');
        }
    }

    /**
     * @return PaymentTermProvider
     */
    protected function getPaymentTermProvider()
    {
        return $this->container->get(PaymentTermProvider::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function getQuoteFormTypeName()
    {
        return QuoteType::class;
    }

    protected function getQuoteRequestHandler(): QuoteRequestHandler
    {
        return $this->container->get(QuoteRequestHandler::class);
    }

    protected function getValidationGroups(Quote $quote): GroupSequence|array|string
    {
        return new GroupSequence([
            Constraint::DEFAULT_GROUP,
            'add_kit_item_line_item',
            'quote_entry_point'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                EventDispatcherInterface::class,
                PaymentTermProvider::class,
                QuoteRequestHandler::class,
            ]
        );
    }
}

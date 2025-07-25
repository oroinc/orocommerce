<?php

namespace Oro\Bundle\SaleBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderComposite;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Oro\Bundle\FormBundle\Provider\SaveAndReturnActionFormTemplateDataProvider;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType;
use Oro\Bundle\SaleBundle\Model\QuoteRequestHandler;
use Oro\Bundle\SaleBundle\Storage\ReturnRouteDataStorage;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Back-office CRUD for quotes.
 */
class QuoteController extends AbstractController
{
    const REDIRECT_BACK_FLAG = 'redirect_back';

    /**
     *
     * @param Quote $quote
     * @return array
     */
    #[Route(path: '/view/{id}', name: 'oro_sale_quote_view', requirements: ['id' => '\d+'])]
    #[Template]
    #[ParamConverter('quote', options: ['repository_method' => 'getQuote'])]
    #[Acl(id: 'oro_sale_quote_view', type: 'entity', class: Quote::class, permission: 'VIEW')]
    public function viewAction(Quote $quote)
    {
        return [
            'entity' => $quote,
        ];
    }

    /**
     *
     * @return array
     */
    #[Route(path: '/', name: 'oro_sale_quote_index')]
    #[Template]
    #[AclAncestor('oro_sale_quote_view')]
    public function indexAction()
    {
        return [
            'entity_class' => Quote::class,
        ];
    }

    /**
     * @param Request $request
     * @return array|Response|RedirectResponse
     */
    #[Route(path: '/create', name: 'oro_sale_quote_create')]
    #[Template('@OroSale/Quote/update.html.twig')]
    #[Acl(id: 'oro_sale_quote_create', type: 'entity', class: Quote::class, permission: 'CREATE')]
    public function createAction(Request $request)
    {
        return $this->createQuote($request);
    }

    /**
     * Create sales quote form for customer
     */
    #[Route(
        path: '/create/customer/{customer}',
        name: 'oro_sale_quote_create_for_customer',
        requirements: ['customer' => '\d+']
    )]
    #[Template('@OroSale/Quote/update.html.twig')]
    #[AclAncestor('oro_sale_quote_create')]
    public function createQuoteForCustomerAction(
        Request $request,
        Customer $customer
    ): array|Response {
        if (!$this->isGranted('VIEW', $customer)) {
            throw $this->createAccessDeniedException();
        }

        $quote = new Quote();
        $quote->setCustomer($customer);

        $saveAndReturnActionFormTemplateDataProvider = $this->container->get(
            SaveAndReturnActionFormTemplateDataProvider::class
        );
        $saveAndReturnActionFormTemplateDataProvider
            ->setSaveFormActionRoute(
                'oro_sale_quote_create_for_customer',
                [
                    'customer' => $customer->getId(),
                ]
            )
            ->setReturnActionRoute(
                'oro_customer_customer_view',
                [
                    'id' => $customer->getId(),
                ],
                'oro_customer_customer_view'
            );

        return $this->createQuote($request, $quote, $saveAndReturnActionFormTemplateDataProvider);
    }

    /**
     * Create sales quote form for customer user
     */
    #[Route(
        path: '/create/customer-user/{customerUser}',
        name: 'oro_sale_quote_create_for_customer_user',
        requirements: ['customerUser' => '\d+']
    )]
    #[Template('@OroSale/Quote/update.html.twig')]
    #[AclAncestor('oro_sale_quote_create')]
    public function createQuoteForCustomerUserAction(
        Request $request,
        CustomerUser $customerUser
    ): array|Response {
        if (!$this->isGranted('VIEW', $customerUser)) {
            throw $this->createAccessDeniedException();
        }

        $quote = new Quote();
        $quote->setCustomerUser($customerUser);
        $quote->setCustomer($customerUser->getCustomer());

        $saveAndReturnActionFormTemplateDataProvider = $this->container->get(
            SaveAndReturnActionFormTemplateDataProvider::class
        );
        $saveAndReturnActionFormTemplateDataProvider
            ->setSaveFormActionRoute(
                'oro_sale_quote_create_for_customer_user',
                [
                    'customerUser' => $customerUser->getId(),
                ]
            )
            ->setReturnActionRoute(
                'oro_customer_customer_user_view',
                [
                    'id' => $customerUser->getId(),
                ],
                'oro_customer_customer_user_view'
            );

        return $this->createQuote($request, $quote, $saveAndReturnActionFormTemplateDataProvider);
    }

    /**
     * @param Quote $quote
     * @param Request $request
     * @return array|RedirectResponse
     */
    #[Route(path: '/update/{id}', name: 'oro_sale_quote_update', requirements: ['id' => '\d+'])]
    #[Template]
    #[ParamConverter('quote', options: ['repository_method' => 'getQuote'])]
    #[Acl(id: 'oro_sale_quote_update', type: 'entity', class: Quote::class, permission: 'EDIT')]
    public function updateAction(Quote $quote, Request $request)
    {
        return $this->update($quote, $request);
    }

    /**
     * @param Quote $quote
     * @return array
     */
    #[Route(path: '/info/{id}', name: 'oro_sale_quote_info', requirements: ['id' => '\d+'])]
    #[Template]
    #[AclAncestor('oro_sale_quote_view')]
    public function infoAction(Quote $quote)
    {
        return [
            'entity' => $quote,
        ];
    }

    /**
     * @param Quote $quote
     * @param Request $request
     * @param FormTemplateDataProviderInterface|null $resultProvider
     * @return array|RedirectResponse
     */
    protected function update(
        Quote $quote,
        Request $request,
        FormTemplateDataProviderInterface|null $resultProvider = null
    ) {
        if (\in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $quoteRequestHandler = $this->container->get(QuoteRequestHandler::class);
            $quote->setCustomer($quoteRequestHandler->getCustomer());
            $quote->setCustomerUser($quoteRequestHandler->getCustomerUser());
        }

        $formTemplateDataProviderComposite = $this->container->get(FormTemplateDataProviderComposite::class)
            ->addFormTemplateDataProviders('quote_update')
            ->addFormTemplateDataProviders($resultProvider);

        $form = $this->createForm(
            QuoteType::class,
            $quote,
            [
                'validation_groups' => $this->getValidationGroups($quote),
            ]
        );

        return $this->container->get(UpdateHandlerFacade::class)->update(
            $quote,
            $form,
            $this->container->get(TranslatorInterface::class)->trans('oro.sale.controller.quote.saved.message'),
            $request,
            null,
            $formTemplateDataProviderComposite
        );
    }

    private function createQuote(
        Request $request,
        ?Quote $quote = null,
        FormTemplateDataProviderInterface|null $resultProvider = null
    ): array|Response {
        $quote = $quote ?? new Quote();

        if ($request->get(self::REDIRECT_BACK_FLAG, false)) {
            return $this->handleRequestAndRedirectBack(
                $request,
                $quote,
                '@OroSale/Quote/createWithReturn.html.twig'
            );
        }

        if (!$request->get(ProductDataStorage::STORAGE_KEY, false)) {
            return $this->update($quote, $request, $resultProvider);
        }

        $this->createForm(QuoteType::class, $quote);

        if (!$quote->getWebsite()) {
            $quote->setWebsite($this->container->get(WebsiteManager::class)->getDefaultWebsite());
        }

        $em = $this->container->get(ManagerRegistry::class)->getManagerForClass(Quote::class);

        $em->persist($quote);
        $em->flush();

        return $this->redirectToRoute('oro_sale_quote_update', ['id' => $quote->getId()]);
    }

    /**
     * Handles request which requires get back after Quote creating
     *
     * @param Request $request
     * @param Quote $quote
     * @param string $template
     *
     * @return RedirectResponse|Response
     */
    private function handleRequestAndRedirectBack(Request $request, Quote $quote, $template)
    {
        // Handle form validate and fetch pre-response
        $updateResponse = $this->update($quote, $request);

        /** @var ReturnRouteDataStorage $redirectStorage */
        $redirectStorage = $this->container->get(ReturnRouteDataStorage::class);
        $routeToRedirectBack = $redirectStorage->get();

        if ($this->isRequestHandledSuccessfully($updateResponse)) {
            // We don't need storage data anymore, so clean it and return user to route which we have to
            $redirectStorage->remove();
            return $this->redirectToRoute($routeToRedirectBack['route'], $routeToRedirectBack['parameters']);
        }

        // Render form with limited number of actions because we will redirect back
        return $this->render(
            $template,
            array_merge($updateResponse, [
                'return_route' => $routeToRedirectBack,
            ])
        );
    }

    /**
     * Returns if request is checked by handler
     *
     * @param $updateResponse
     * @return bool
     */
    private function isRequestHandledSuccessfully($updateResponse)
    {
        return $updateResponse instanceof RedirectResponse;
    }

    protected function getValidationGroups(Quote $quote): GroupSequence|array|string
    {
        return new GroupSequence([
            Constraint::DEFAULT_GROUP,
            'add_kit_item_line_item',
            $quote->getId() ? 'quote_update' : 'quote_create'
        ]);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                QuoteRequestHandler::class,
                WebsiteManager::class,
                TranslatorInterface::class,
                UpdateHandlerFacade::class,
                ReturnRouteDataStorage::class,
                SaveAndReturnActionFormTemplateDataProvider::class,
                FormTemplateDataProviderComposite::class,
                ManagerRegistry::class,
            ]
        );
    }
}

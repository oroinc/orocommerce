#Upgrade to rc.1

##General
- Changed minimum required php version to 5.6

##CheckoutBundle
- `Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface::getSourceEntity` returned value fixed `@return CheckoutSourceEntityInterface|null`
- `Oro\Bundle\CheckoutBundle\EventListener\ResolvePaymentTermListener`:
  * changed 2nd argument of constructor from `Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher` to `Doctrine\Common\Persistence\ManagerRegistry $registry`
  * added 3rd argument to constructor `PaymentTermProvider $paymentTermProvider`
- Removed classes:
    - `Oro\Bundle\CheckoutBundle\Event\CheckoutEvents`
    - `Oro\Bundle\CheckoutBundle\EventListener\CheckoutEntityListener`
    - `Oro\Bundle\CheckoutBundle\Model\Action\StartCheckout` and action `@start_checkout`
- Removed operations:
  * `oro_shoppinglist_frontend_createorder`
  * `oro_shoppinglist_frontend_products_quick_add_to_checkout`

##CustomerBundle
- `Oro\Bundle\CustomerBundle\Entity\AccountGroup` made extendable

##FrontendBundle
- `oro_frontend.listener.datagrid.fields` and `oro_frontend.listener.enum_filter_frontend_listener` priority fixed to make them executed first
- Deleted `Oro\Bundle\FrontendBundle\Helper\ActionApplicationsHelper`.
Please use `Oro\Bundle\FrontendBundle\Provider\ActionCurrentApplicationProvider` and `Oro\Bundle\FrontendBundle\ActionProvider\RouteProvider` instead.
- Added API controller `Oro\Bundle\FrontendBundle\Controller\Api\Rest\WorkflowController`
- Added controller `Oro\Bundle\FrontendBundle\Controller\WorkflowController`
- Added controller `Oro\Bundle\FrontendBundle\Controller\WorkflowWidgetController`

##OrderBundle
- `Oro\Bundle\SaleBundle\Entity\Quote` `paymentTerm` removed with getter `getPaymentTerm` and setter `setPaymentTerm`, use `oro_payment_term.provider.payment_term_association` to assign PaymentTerm to entity
- `Oro\Bundle\SaleBundle\Form\Type\QuoteType` `SecurityFacade $securityFacade` and `PaymentTermProvider $paymentTermProvider` removed, use `\Oro\Bundle\PaymentTermBundle\Form\Extension\PaymentTermAclExtension` instead
- Changes in `Oro\Bundle\OrderBundle\EventListener\TotalCalculateListener`:
    - second constructor argument changed to `Oro\Bundle\ActionBundle\Helper\ApplicationsHelperInterface` instead of `Oro\Bundle\ActionBundle\Helper\ApplicationsHelper`
    - used `Oro\Bundle\ActionBundle\Helper\ApplicationsHelper::DEFAULT_APPLICATION` instead of `Oro\Bundle\FrontendBundle\Helper\ActionApplicationsHelper::DEFAULT_APPLICATION`

##SaleBundle
- `Oro\Bundle\OrderBundle\Entity\Order` `paymentTerm` removed with getter `getPaymentTerm` and setter `setPaymentTerm`, use `oro_payment_term.provider.payment_term_association` to assign PaymentTerm to entity
- `Oro\Bundle\OrderBundle\Form\Type\FrontendOrderType` `SecurityFacade $securityFacade` and `PaymentTermProvider $paymentTermProvider` removed, use `\Oro\Bundle\PaymentTermBundle\Form\Extension\PaymentTermAclExtension` instead
- `Oro\Bundle\OrderBundle\Form\Type\OrderType` `SecurityFacade $securityFacade` and `PaymentTermProvider $paymentTermProvider` removed, use `\Oro\Bundle\PaymentTermBundle\Form\Extension\PaymentTermAclExtension` instead

##ShoppingListBundle
- `Oro\Bundle\ShoppingListBundle\Processor\QuickAddCheckoutProcessor`:
  * removed public methods `setOperationManager(OperationManager $operationManager)` and `setOperationName($operationName)`
  * added public methods `setActionGroupRegistry(ActionGroupRegistry $actionGroupRegistry)` and `setActionGroupName($groupName)` instead

##PaymentBundle
- All code related to `PaymentTerm` moved to `PaymentTermBundle`. Significant changes listed below
- Class `Oro\Bundle\PaymentBundle\Entity\PaymentTerm` to `Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm`
- Class `Oro\Bundle\PaymentBundle\Method\PaymentTerm` to `Oro\Bundle\PaymentTermBundle\Method\PaymentTerm`
- Class `Oro\Bundle\PaymentBundle\Provider\PaymentTermProvider` to `Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider`
- Service `oro_payment.provider.payment_term` to `oro_payment_term.provider.payment_term`
- Event `oro_payment.resolve.payment_term` to `oro_payment_term.resolve.payment_term`
- Template `OroPaymentBundle:layouts:default\templates\order_review.html.twig` to `OroPaymentTermBundle:layouts:default\templates\order_review.html.twig`
- Template `OroPaymentBundle:layouts:default\templates\payment.html.twig` to `OroPaymentTermBundle:layouts:default\templates\payment.html.twig`
- Template `OroPaymentBundle:layouts:default\templates\order_review.html.twig` to `OroPaymentTermBundle:layouts:default\templates\order_review.html.twig`
- PaymentTerm Configuration from `Oro\Bundle\PaymentBundle\DependencyInjection\Configuration` moved to `Oro\Bundle\PaymentTermBundle\DependencyInjection\Configuration`
- `Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTermRepository` was removed
- Class `Oro\Bundle\PaymentBundle\EventListener\DatagridListener` to `Oro\Bundle\PaymentTermBundle\EventListener\AccountDatagridListener`
- Class `Oro\Bundle\PaymentBundle\EventListener\FormViewListener` to `Oro\Bundle\PaymentTermBundle\EventListener\ValueRenderEventListener`
- Class `Oro\Bundle\PaymentBundle\Form\Extension\AbstractPaymentTermExtension` to `\Oro\Bundle\PaymentTermBundle\Form\Extension\PaymentTermExtension`
- Class `Oro\Bundle\PaymentBundle\Form\Extension\AccountFormExtension` to `\Oro\Bundle\PaymentTermBundle\Form\Extension\AccountFormExtension`
- Class `Oro\Bundle\PaymentBundle\Form\Extension\AccountGroupFormExtension` to `\Oro\Bundle\PaymentTermBundle\Form\Extension\AccountFormExtension`
- `Oro\Bundle\PaymentBundle\Form\Handler\PaymentTermHandler` removed
- Class `Oro\Bundle\PaymentBundle\Form\Type\PaymentTermType` to `Oro\Bundle\PaymentTermBundle\Form\Type\PaymentTermType`
- Class `Oro\Bundle\PaymentBundle\Method\Config\PaymentTermConfigInterface` to `Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface`

##WarehouseBundle
- `Oro\Bundle\WarehouseBundle\EventListener\OrderLineItemWarehouseGridListener` removed
- `Oro\Bundle\WarehouseBundle\EventListener\OrderWarehouseGridListener` removed

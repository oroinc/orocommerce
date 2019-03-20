- [PaymentTermBundle](#paymenttermbundle)
- [ShoppingListBundle](#shoppinglistbundle)

PaymentTermBundle
-----------------
* The `CustomerDatagridListener::__construct(PaymentTermAssociationProvider $paymentTermAssociationProvider)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.1.1/src/Oro/Bundle/PaymentTermBundle/EventListener/CustomerDatagridListener.php#L18 "Oro\Bundle\PaymentTermBundle\EventListener\CustomerDatagridListener")</sup> method was changed to `CustomerDatagridListener::__construct(PaymentTermAssociationProvider $paymentTermAssociationProvider, SelectedFieldsProviderInterface $selectedFieldsProvider)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.1.2/src/Oro/Bundle/PaymentTermBundle/EventListener/CustomerDatagridListener.php#L28 "Oro\Bundle\PaymentTermBundle\EventListener\CustomerDatagridListener")</sup>

ShoppingListBundle
------------------
* The `LineItemHandler::__construct(FormInterface $form, Request $request, ManagerRegistry $doctrine, ShoppingListManager $shoppingListManager, CurrentShoppingListManager $currentShoppingListManager)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.1.1/src/Oro/Bundle/ShoppingListBundle/Form/Handler/LineItemHandler.php#L43 "Oro\Bundle\ShoppingListBundle\Form\Handler\LineItemHandler")</sup> method was changed to `LineItemHandler::__construct(FormInterface $form, Request $request, ManagerRegistry $doctrine, ShoppingListManager $shoppingListManager, CurrentShoppingListManager $currentShoppingListManager, ValidatorInterface $validator)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.1.2/src/Oro/Bundle/ShoppingListBundle/Form/Handler/LineItemHandler.php#L51 "Oro\Bundle\ShoppingListBundle\Form\Handler\LineItemHandler")</sup>


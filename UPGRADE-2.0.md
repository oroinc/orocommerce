UPGRADE NOTES
=============

CheckoutBundle:
---------------
- Method `checkoutAction` in `OroB2B\Bundle\CheckoutBundle\Controller\Frontend\CheckoutController` changed signature to `checkoutAction(Request $request, WorkflowItem $workflowItem)`.
- Added parameter `WorkflowManager $workflowManager` to constructor of `OroB2B\Bundle\CheckoutBundle\Model\Action\StartCheckout`;
- Removed fields `workflowItem` and `workflowStep` fron entity `OroB2B\Bundle\CheckoutBundle\Entity\BaseCheckout` - not using `WorkflowAwareTrait` more. It means that for entity `OroB2B\Bundle\CheckoutBundle\Entity\Checkout` these fields removed too. 

AlternativeCheckoutBundle:
--------------------------
- Removed fields `workflowItem` and `workflowStep` from entity `OroB2B\Bundle\AlternativeCheckoutBundle\Entity\AlternativeCheckout` - not using `WorkflowAwareTrait` more.

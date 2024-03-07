<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Updates shipping method view in "order_confirmation_email" email template.
 */
class UpdateShippingMethodInOrderConfirmationEmailTemplate extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [AddKitItemLineItemsToOrderConfirmationEmailTemplate::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $existingTemplate = $this->findExistingTemplate($manager);
        if (null === $existingTemplate) {
            return;
        }
        $content = $existingTemplate->getContent();
        if (!$content) {
            return;
        }

        $existingTemplate->setContent(str_replace(
            'oro_order_shipping_method_label(entity.shippingMethod, entity.shippingMethodType)',
            'oro_order_shipping_method_label(entity.shippingMethod, entity.shippingMethodType, entity.organization)',
            $content
        ));
        $manager->flush();
    }

    private function findExistingTemplate(ObjectManager $manager): ?EmailTemplate
    {
        return $manager->getRepository(EmailTemplate::class)->findOneBy([
            'name' => 'order_confirmation_email',
            'entityName' => Order::class
        ]);
    }
}

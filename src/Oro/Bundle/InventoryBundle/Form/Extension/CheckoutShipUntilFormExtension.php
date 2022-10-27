<?php

namespace Oro\Bundle\InventoryBundle\Form\Extension;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Form\Type\CheckoutShipUntilType;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Extends CheckoutShipUntilType with new ship until options
 */
class CheckoutShipUntilFormExtension extends AbstractTypeExtension
{
    /**
     * @var UpcomingProductProvider
     */
    protected $provider;

    /**
     * @var CheckoutLineItemsManager
     */
    protected $checkoutLineItemsManager;

    /**
     * @var DateTimeFormatterInterface
     */
    protected $dateTimeFormatter;

    public function __construct(
        UpcomingProductProvider $provider,
        CheckoutLineItemsManager $checkoutLineItemsManager,
        DateTimeFormatterInterface $dateTimeFormatter
    ) {
        $this->provider = $provider;
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
        $this->dateTimeFormatter = $dateTimeFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [CheckoutShipUntilType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('_products', function (Options $options) {
            return $this->getProducts($options);
        });

        $resolver->setDefault('disabled', function (Options $options) {
            foreach ($options['_products'] as $product) {
                if ($this->provider->isUpcoming($product) && !$this->provider->getAvailabilityDate($product)) {
                    return true;
                }
            }
            return false;
        });

        $resolver->setDefault('minDate', function (Options $options) {
            $latestDate = $this->provider->getLatestAvailabilityDate($options['_products']);
            return $latestDate ? $this->dateTimeFormatter->formatDate($latestDate) : '0';
        });
    }

    /**
     * @param Options $options
     * @return Product[]
     */
    protected function getProducts(Options $options)
    {
        $checkout = $options['checkout'];
        if (!$checkout instanceof Checkout) {
            throw new \LogicException('Wrong "checkout" option value');
        }
        $products = [];
        foreach ($this->checkoutLineItemsManager->getData($checkout) as $lineItem) {
            $product = $lineItem->getProduct();
            if ($product instanceof Product) {
                $products[] = $product;
            }
        }
        return $products;
    }
}

<?php

namespace Oro\Bundle\PaymentTermBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const PAYMENT_TERM_LABEL_KEY = 'payment_term_label';
    const PAYMENT_TERM_SHORT_LABEL_KEY = 'payment_term_short_label';

    const PAYMENT_TERM_LABEL = 'Payment Terms';

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root(OroPaymentTermExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                // Payment Term
                self::PAYMENT_TERM_LABEL_KEY => [
                    'type' => 'text',
                    'value' => self::PAYMENT_TERM_LABEL,
                ],
                self::PAYMENT_TERM_SHORT_LABEL_KEY => [
                    'type' => 'text',
                    'value' => self::PAYMENT_TERM_LABEL,
                ],
            ]
        );

        return $treeBuilder;
    }
}

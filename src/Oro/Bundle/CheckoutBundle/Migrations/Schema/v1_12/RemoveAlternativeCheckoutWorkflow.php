<?php
declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\TranslationBundle\Migration\DeleteTranslationKeysQuery;
use Oro\Bundle\TranslationBundle\Migration\DeleteTranslationsByDomainAndKeyPrefixQuery;
use Oro\Bundle\WorkflowBundle\Migration\RemoveWorkflowAwareEntitiesQuery;
use Oro\Bundle\WorkflowBundle\Migration\RemoveWorkflowDefinitionQuery;

/**
 * Removes the remaining workflow from the AlternativeCheckoutBundle and unnecessary translations.
 */
class RemoveAlternativeCheckoutWorkflow implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (\class_exists('Oro\Bundle\AlternativeCheckoutBundle\OroAlternativeCheckoutBundle', false)) {
            return;
        }

        $queries->addQuery(
            new RemoveWorkflowAwareEntitiesQuery('b2b_flow_alternative_checkout', Checkout::class, 'oro_checkout')
        );
        $queries->addQuery(new RemoveWorkflowDefinitionQuery('b2b_flow_alternative_checkout'));
        $queries->addQuery(new DeleteTranslationKeysQuery('messages', ['Alternative Checkout']));
        $queries->addQuery(new DeleteTranslationKeysQuery('workflows', ['Alternative Checkout']));
        $queries->addQuery(
            new DeleteTranslationsByDomainAndKeyPrefixQuery('messages', 'oro.alternativecheckout.')
        );
        $queries->addQuery(
            new DeleteTranslationsByDomainAndKeyPrefixQuery('workflows', 'oro.workflow.b2b_flow_alternative_checkout.')
        );
    }
}

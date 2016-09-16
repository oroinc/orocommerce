<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Validator;

use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRules;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\PricingBundle\Validator\Constraints\LexemeCircularReference;
use Oro\Bundle\PricingBundle\Validator\Constraints\LexemeCircularReferenceValidator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class LexemeCircularReferenceValidatorTest extends WebTestCase
{
    /**
     * @var LexemeCircularReferenceValidator
     */
    protected $validator;

    /**
     * @var LexemeCircularReference
     */
    protected $constraint;

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                LoadProductPrices::class,
                LoadPriceRules::class
            ]
        );

        $this->validator = $this->getContainer()->get('validator');
        $this->constraint = new LexemeCircularReference(['fields' => ['productAssignmentRule']]);
    }

    public function testValidateNotCircularWithoutPriceRulesReference()
    {
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $referencedPriceList = $this->getReference(LoadPriceLists::PRICE_LIST_2);
        $finalPriceList = $this->getReference(LoadPriceLists::PRICE_LIST_3);

        //not circular reference with 3 elements
        $priceList->setProductAssignmentRule(sprintf(
            'pricelist[%s].productAssignmentRule',
            $referencedPriceList->getId()
        ));

        $referencedPriceList->setProductAssignmentRule(sprintf(
            'pricelist[%s].productAssignmentRule',
            $finalPriceList->getId()
        ));

        $errors = $this->validator->validate($priceList, $this->constraint);
        $this->assertEquals(0, $errors->count());

        //not circular reference with binary node
        $priceList->setProductAssignmentRule(sprintf(
            'pricelist[%s].productAssignmentRule and pricelist[%s].productAssignmentRule',
            $referencedPriceList->getId(),
            $finalPriceList->getId()
        ));
        $referencedPriceList->setProductAssignmentRule(null);

        $errors = $this->validator->validate($priceList, $this->constraint);
        $this->assertEquals(0, $errors->count());
    }

    public function testValidateCircularWithoutPriceRulesReference()
    {
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $referencedPriceList = $this->getReference(LoadPriceLists::PRICE_LIST_2);
        $finalPriceList = $this->getReference(LoadPriceLists::PRICE_LIST_3);

        //circular reference with 3 elements
        $priceList->setProductAssignmentRule(sprintf(
            'pricelist[%s].productAssignmentRule',
            $referencedPriceList->getId()
        ));

        $referencedPriceList->setProductAssignmentRule(sprintf(
            'pricelist[%s].productAssignmentRule',
            $finalPriceList->getId()
        ));

        $finalPriceList->setProductAssignmentRule(sprintf(
            'pricelist[%s].productAssignmentRule',
            $priceList->getId()
        ));

        $errors = $this->validator->validate($priceList, $this->constraint);

        $this->assertEquals(1, $errors->count());
        $this->assertEquals('Circular references are not allowed', $errors[0]->getMessage());

        //circular reference with binary node
        $nonCircularPriceList = $this->getReference(LoadPriceLists::PRICE_LIST_4);

        $priceList->setProductAssignmentRule(sprintf(
            'pricelist[%s].productAssignmentRule and pricelist[%s].productAssignmentRule',
            $nonCircularPriceList->getId(),
            $referencedPriceList->getId()
        ));

        $errors = $this->validator->validate($priceList, $this->constraint);
        $this->assertEquals(1, $errors->count());
        $this->assertEquals('Circular references are not allowed', $errors[0]->getMessage());
    }

    public function testValidateNotCircularWithPriceRulesReference()
    {
        $priceList1 = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $priceList2 = $this->getReference(LoadPriceLists::PRICE_LIST_2);
        $priceList3 = $this->getReference(LoadPriceLists::PRICE_LIST_4);

        $priceRule1 = $this->getReference(LoadPriceRules::PRICE_RULE_1);
        $priceRule2 = $this->getReference(LoadPriceRules::PRICE_RULE_2);

        //not circular reference with 3 PL and 5 PR
        $priceList2->setProductAssignmentRule(sprintf(
            'pricelist[%s].productAssignmentRule',
            $priceList1->getId()
        ));

        $priceList1->setProductAssignmentRule(null);
        $priceList3->setProductAssignmentRule(null);
        $priceRule1->setRule(sprintf('pricelist[%s].productAssignmentRule', $priceList3->getId()));
        $priceRule2->setRuleCondition(sprintf(
            'product.id in pricelist[%s].assignedProducts or pricelist[%s].prices.quantity > 10',
            $priceList3->getId(),
            $priceList3->getId()
        ));

        $errors = $this->validator->validate($priceList2, $this->constraint);
        $this->assertEquals(0, $errors->count());
    }

    public function testValidateCircularWithPriceRulesReference()
    {
        $priceList1 = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $priceList2 = $this->getReference(LoadPriceLists::PRICE_LIST_2);
        $priceList3 = $this->getReference(LoadPriceLists::PRICE_LIST_4);

        $priceRule1 = $this->getReference(LoadPriceRules::PRICE_RULE_1);
        $priceRule2 = $this->getReference(LoadPriceRules::PRICE_RULE_2);
        $priceRule5 = $this->getReference(LoadPriceRules::PRICE_RULE_5);

        //not circular reference with 3 PL and 5 PR
        $priceList2->setProductAssignmentRule(sprintf(
            'pricelist[%s].productAssignmentRule',
            $priceList1->getId()
        ));

        $priceRule1->setRule(sprintf('pricelist[%s].productAssignmentRule', $priceList3->getId()));
        $priceRule2->setRuleCondition(sprintf(
            'product.id in pricelist[%s].assignedProducts or pricelist[%s].prices.quantity > 10',
            $priceList3->getId(),
            $priceList3->getId()
        ));
        $priceRule5->setRuleCondition(sprintf('pricelist[%s].prices.quantity > 10', $priceList2->getId()));

        $errors = $this->validator->validate($priceList2, $this->constraint);

        $this->assertEquals(1, $errors->count());
        $this->assertEquals('Circular references are not allowed', $errors[0]->getMessage());
    }

    public function testValidatePriceRuleNotCircularWithPriceRulesReference()
    {
        $priceList1 = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $priceList2 = $this->getReference(LoadPriceLists::PRICE_LIST_2);
        $priceList3 = $this->getReference(LoadPriceLists::PRICE_LIST_4);

        $priceRule1 = $this->getReference(LoadPriceRules::PRICE_RULE_1);
        $priceRule2 = $this->getReference(LoadPriceRules::PRICE_RULE_2);
        $priceRule5 = $this->getReference(LoadPriceRules::PRICE_RULE_5);

        $priceRule5->setRule(sprintf('pricelist[%s].productAssignmentRule', $priceList2->getId()));

        $priceList2->setProductAssignmentRule(sprintf(
            'pricelist[%s].productAssignmentRule',
            $priceList1->getId()
        ));
        $priceList1->setProductAssignmentRule(null);
        $priceList2->setProductAssignmentRule(null);
        $priceList3->setProductAssignmentRule(null);
        $priceRule1->setRule(sprintf('pricelist[%s].productAssignmentRule', $priceList3->getId()));
        $priceRule2->setRuleCondition(sprintf(
            'product.id in pricelist[%s].assignedProducts or pricelist[%s].prices.quantity > 10',
            $priceList3->getId(),
            $priceList3->getId()
        ));

        $constraint = new LexemeCircularReference(['fields' => ['rule']]);

        $errors = $this->validator->validate($priceRule5, $constraint);
        $this->assertEquals(0, $errors->count());
    }

    public function testValidatePriceRuleCircularWithPriceRulesReference()
    {
        $priceList1 = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $priceList2 = $this->getReference(LoadPriceLists::PRICE_LIST_2);
        $priceList3 = $this->getReference(LoadPriceLists::PRICE_LIST_4);

        $priceRule1 = $this->getReference(LoadPriceRules::PRICE_RULE_1);
        $priceRule2 = $this->getReference(LoadPriceRules::PRICE_RULE_2);
        $priceRule5 = $this->getReference(LoadPriceRules::PRICE_RULE_5);

        $priceRule5->setRule(sprintf('pricelist[%s].productAssignmentRule', $priceList2->getId()));

        $priceList2->setProductAssignmentRule(sprintf(
            'pricelist[%s].productAssignmentRule',
            $priceList1->getId()
        ));
        $priceList1->setProductAssignmentRule(null);
        $priceList3->setProductAssignmentRule(null);
        $priceRule1->setRule(sprintf('pricelist[%s].productAssignmentRule', $priceList3->getId()));
        $priceRule2->setRuleCondition(sprintf(
            'product.id in pricelist[%s].assignedProducts or pricelist[%s].prices.quantity > 10',
            $priceList3->getId(),
            $priceList3->getId()
        ));

        $constraint = new LexemeCircularReference(['fields' => ['rule']]);

        $errors = $this->validator->validate($priceRule5, $constraint);
        $this->assertEquals(1, $errors->count());
        $this->assertEquals('Circular references are not allowed', $errors[0]->getMessage());
    }
}

<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Response;

class ResponseStatusMap
{
    const APPROVED = '0';
    const USER_AUTHENTICATION_FAILED = '1';
    const INVALID_TENDER_TYPE = '2';
    const INVALID_TRANSACTION_TYPE = '3';
    const INVALID_AMOUNT_FORMAT = '4';
    const INVALID_MERCHANT_INFORMATION = '5';
    const INVALID_OR_UNSUPPORTED_CURRENCY_CODE = '6';
    const FIELD_FORMAT_ERROR = '7';
    const NOT_A_TRANSACTION_SERVER = '8';
    const TOO_MANY_PARAMETERS_OR_INVALID_STREAM = '9';
    const TOO_MANY_LINE_ITEMS = '10';
    const CLIENT_TIME_OUT_WAITING_FOR_RESPONSE = '11';
    const DECLINED = '12';
    const REFERRAL = '13';
    const ORIGINAL_TRANSACTION_ID_NOT_FOUND = '19';
    const CANNOT_FIND_THE_CUSTOMER_REFERENCE_NUMBER = '20';
    const INVALID_ABA_NUMBER = '22';
    const INVALID_ACCOUNT_NUMBER = '23';
    const INVALID_EXPIRATION_DATE = '24';
    const INVALID_HOST_MAPPING = '25';
    const INVALID_VENDOR_ACCOUNT = '26';
    const INSUFFICIENT_PARTNER_PERMISSIONS = '27';
    const INSUFFICIENT_USER_PERMISSIONS = '28';
    const INVALID_XML_DOCUMENT = '29';
    const DUPLICATE_TRANSACTION = '30';
    const ERROR_IN_ADDING_THE_RECURRING_PROFILE = '31';
    const ERROR_IN_MODIFYING_THE_RECURRING_PROFILE = '32';
    const ERROR_IN_CANCELING_THE_RECURRING_PROFILE = '33';
    const ERROR_IN_FORCING_THE_RECURRING_PROFILE = '34';
    const ERROR_IN_REACTIVATING_THE_RECURRING_PROFILE = '35';
    const OLTP_TRANSACTION_FAILED = '36';
    const INVALID_RECURRING_PROFILE_ID = '37';
    const INSUFFICIENT_FUNDS_AVAILABLE_IN_ACCOUNT = '50';
    const EXCEEDS_PER_TRANSACTION_LIMIT = '51';
    const PERMISSION_ISSUE = '52';
    const GENERAL_ERROR = '99';
    const TRANSACTION_TYPE_NOT_SUPPORTED_BY_HOST = '100';
    const TIME_OUT_VALUE_TOO_SMALL = '101';
    const PROCESSOR_NOT_AVAILABLE = '102';
    const ERROR_READING_RESPONSE_FROM_HOST = '103';
    const TIMEOUT_WAITING_FOR_PROCESSOR_RESPONSE = '104';
    const CREDIT_ERROR = '105';
    const HOST_NOT_AVAILABLE = '106';
    const DUPLICATE_SUPPRESSION_TIME_OUT = '107';
    const VOID_ERROR = '108';
    const TIME_OUT_WAITING_FOR_HOST_RESPONSE = '109';
    const REFERENCED_AUTH_AGAINST_ORDER_ERROR = '110';
    const CAPTURE_ERROR = '111';
    const FAILED_AVS_CHECK = '112';
    const MERCHANT_SALE_TOTAL_WILL_EXCEED_THE_SALES_CAP_WITH_CURRENT_TRANSACTION = '113';
    const CARD_SECURITY_CODE_CSC_MISMATCH = '114';
    const SYSTEM_BUSY_TRY_AGAIN_LATER = '115';
    const FAILED_TO_LOCK_TERMINAL_NUMBER = '116';
    const FAILED_MERCHANT_RULE_CHECK = '117';
    const INVALID_KEYWORDS_FOUND_IN_STRING_FIELDS = '118';
    const ATTEMPT_TO_REFERENCE_A_FAILED_TRANSACTION = '120';
    const NOT_ENABLED_FOR_FEATURE = '121';
    const MERCHANT_SALE_TOTAL_WILL_EXCEED_THE_CREDIT_CAP_WITH_CURRENT_TRANSACTION = '122';

    // FPSF = Fraud Protection Services Filter
    const FPSF_DECLINED_BY_FILTERS = '125';
    const FPSF_FLAGGED_FOR_REVIEW_BY_FILTERS = '126';
    const FPSF_NOT_PROCESSED_BY_FILTERS = '127';
    const FPSF_DECLINED_BY_MERCHANT_AFTER_BEING_FLAGGED_FOR_REVIEW_BY_FILTERS = '128';
    const CARD_HAS_NOT_BEEN_SUBMITTED_FOR_UPDATE = '132';
    const DATA_MISMATCH_IN_HTTP_RETRY_REQUEST = '133';
    const ISSUING_BANK_TIMED_OUT = '150';
    const ISSUING_BANK_UNAVAILABLE = '151';
    const SECURE_TOKEN_ALREADY_BEEN_USED = '160';
    const TRANSACTION_USING_SECURE_TOKEN_IS_ALREADY_IN_PROGRESS = '161';
    const SECURE_TOKEN_EXPIRED = '162';
    const REAUTH_ERROR = '200';
    const ORDER_ERROR = '201';
    const CYBERCASH_BATCH_ERROR = '600';
    const CYBERCASH_QUERY_ERROR = '601';
    const GENERIC_HOST_ERROR = '1000';

    // BAS = Buyer Authentication service
    const BUYER_AUTHENTICATION_SERVICE_UNAVAILABLE = '1001';
    const BAS_TRANSACTION_TIMEOUT = '1002';
    const BAS_INVALID_CLIENT_VERSION = '1003';
    const BAS_INVALID_TIMEOUT_VALUE = '1004';
    const BUYER_AUTHENTICATION_SERVICE_UNAVAILABLE_1011 = '1011';
    const BUYER_AUTHENTICATION_SERVICE_UNAVAILABLE_1012 = '1012';
    const BUYER_AUTHENTICATION_SERVICE_UNAVAILABLE_1013 = '1013';
    const BAS_MERCHANT_IS_NOT_ENROLLED_FOR_BUYER_AUTHENTICATION_SERVICE_3D_SECURE = '1014';
    const BAS_3_D_SECURE_ERROR_RESPONSE_RECEIVED = '1016';
    const BAS_3_D_SECURE_ERROR_RESPONSE_IS_INVALID = '1017';
    const BAS_INVALID_CARD_TYPE = '1021';
    const BAS_INVALID_OR_MISSING_CURRENCY_CODE = '1022';
    const BAS_MERCHANT_STATUS_FOR_3D_SECURE_IS_INVALID = '1023';

    // VAF = Validation Authentication failed
    const BAS_VAF_MISSING_OR_INVALID_PARES = '1041';
    const BAS_VAF_PARES_FORMAT_IS_INVALID = '1042';
    const BAS_VAF_CANNOT_FIND_SUCCESSFUL_VERIFY_ENROLLMENT = '1043';
    const BAS_VAF_SIGNATURE_VALIDATION_FAILED_FOR_PARES = '1044';
    const BAS_VAF_MISMATCHED_OR_INVALID_AMOUNT_IN_PARES = '1045';
    const BAS_VAF_MISMATCHED_OR_INVALID_ACQUIRER_IN_PARES = '1046';
    const BAS_VAF_MISMATCHED_OR_INVALID_MERCHANT_ID_IN_PARES = '1047';
    const BAS_VAF_MISMATCHED_OR_INVALID_CARD_NUMBER_IN_PARES = '1048';
    const BAS_VAF_MISMATCHED_OR_INVALID_CURRENCY_CODE_IN_PARES = '1049';
    const BAS_VAF_MISMATCHED_OR_INVALID_XID_IN_PARES = '1050';
    const BAS_VAF_MISMATCHED_OR_INVALID_ORDER_DATE_IN_PARES = '1051';
    const BAS_VAF_THIS_PARES_WAS_ALREADY_VALIDATED_FOR_A_PREVIOUS_VALIDATE_AUTHENTICATION_TRANSACTION = '1052';

    /**
     * @var array
     */
    protected static $messages = [
        self::APPROVED => 'Approved',
        self::USER_AUTHENTICATION_FAILED => 'User authentication failed.',
        self::INVALID_TENDER_TYPE => 'Invalid tender type.',
        self::INVALID_TRANSACTION_TYPE => 'Invalid transaction type.',
        self::INVALID_AMOUNT_FORMAT =>
            'Invalid amount format. Use the format: "### ##.##"  Do not include currency symbols or commas.',
        self::INVALID_MERCHANT_INFORMATION => 'Invalid merchant information.',
        self::INVALID_OR_UNSUPPORTED_CURRENCY_CODE => 'Invalid or unsupported currency code',
        self::FIELD_FORMAT_ERROR => 'Field format error.',
        self::NOT_A_TRANSACTION_SERVER => 'Not a transaction server',
        self::TOO_MANY_PARAMETERS_OR_INVALID_STREAM => 'Too many parameters or invalid stream',
        self::TOO_MANY_LINE_ITEMS => 'Too many line items',
        self::CLIENT_TIME_OUT_WAITING_FOR_RESPONSE => 'Client time-out waiting for response',
        self::DECLINED => 'Declined.',
        self::REFERRAL => 'Referral.',
        self::ORIGINAL_TRANSACTION_ID_NOT_FOUND => 'Original transaction ID not found.',
        self::CANNOT_FIND_THE_CUSTOMER_REFERENCE_NUMBER => 'Cannot find the customer reference number',
        self::INVALID_ABA_NUMBER => 'Invalid ABA number',
        self::INVALID_ACCOUNT_NUMBER => 'Invalid account number.',
        self::INVALID_EXPIRATION_DATE => 'Invalid expiration date.',
        self::INVALID_HOST_MAPPING => 'Invalid Host Mapping.',
        self::INVALID_VENDOR_ACCOUNT => 'Invalid vendor account.',
        self::INSUFFICIENT_PARTNER_PERMISSIONS => 'Insufficient partner permissions',
        self::INSUFFICIENT_USER_PERMISSIONS => 'Insufficient user permissions',
        self::INVALID_XML_DOCUMENT => 'Invalid XML document.',
        self::DUPLICATE_TRANSACTION => 'Duplicate transaction',
        self::ERROR_IN_ADDING_THE_RECURRING_PROFILE => 'Error in adding the recurring profile',
        self::ERROR_IN_MODIFYING_THE_RECURRING_PROFILE => 'Error in modifying the recurring profile',
        self::ERROR_IN_CANCELING_THE_RECURRING_PROFILE => 'Error in canceling the recurring profile',
        self::ERROR_IN_FORCING_THE_RECURRING_PROFILE => 'Error in forcing the recurring profile',
        self::ERROR_IN_REACTIVATING_THE_RECURRING_PROFILE => 'Error in reactivating the recurring profile',
        self::OLTP_TRANSACTION_FAILED => 'OLTP Transaction failed',
        self::INVALID_RECURRING_PROFILE_ID => 'Invalid recurring profile ID',
        self::INSUFFICIENT_FUNDS_AVAILABLE_IN_ACCOUNT => 'Insufficient funds available in account',
        self::EXCEEDS_PER_TRANSACTION_LIMIT => 'Exceeds per transaction limit',
        self::PERMISSION_ISSUE => 'Permission issue.',
        self::GENERAL_ERROR => 'General error.',
        self::TRANSACTION_TYPE_NOT_SUPPORTED_BY_HOST => 'Transaction type not supported by host',
        self::TIME_OUT_VALUE_TOO_SMALL => 'Time-out value too small',
        self::PROCESSOR_NOT_AVAILABLE => 'Processor not available',
        self::ERROR_READING_RESPONSE_FROM_HOST => 'Error reading response from host',
        self::TIMEOUT_WAITING_FOR_PROCESSOR_RESPONSE => 'Timeout waiting for processor response.',
        self::CREDIT_ERROR => 'Credit error.',
        self::HOST_NOT_AVAILABLE => 'Host not available',
        self::DUPLICATE_SUPPRESSION_TIME_OUT => 'Duplicate suppression time-out',
        self::VOID_ERROR => 'Void error.',
        self::TIME_OUT_WAITING_FOR_HOST_RESPONSE => 'Time-out waiting for host response',
        self::REFERENCED_AUTH_AGAINST_ORDER_ERROR => 'Referenced auth (against order) Error',
        self::CAPTURE_ERROR => 'Capture error.',
        self::FAILED_AVS_CHECK => 'Failed AVS check.',
        self::MERCHANT_SALE_TOTAL_WILL_EXCEED_THE_SALES_CAP_WITH_CURRENT_TRANSACTION =>
            'Merchant sale total will exceed the sales cap with current transaction.',
        self::CARD_SECURITY_CODE_CSC_MISMATCH => 'Card Security Code (CSC) Mismatch.',
        self::SYSTEM_BUSY_TRY_AGAIN_LATER => 'System busy, try again later',
        self::FAILED_TO_LOCK_TERMINAL_NUMBER => 'Failed to lock terminal number.',
        self::FAILED_MERCHANT_RULE_CHECK => 'Failed merchant rule check.',
        self::INVALID_KEYWORDS_FOUND_IN_STRING_FIELDS => 'Invalid keywords found in string fields',
        self::ATTEMPT_TO_REFERENCE_A_FAILED_TRANSACTION => 'Attempt to reference a failed transaction',
        self::NOT_ENABLED_FOR_FEATURE => 'Not enabled for feature',
        self::MERCHANT_SALE_TOTAL_WILL_EXCEED_THE_CREDIT_CAP_WITH_CURRENT_TRANSACTION =>
            'Merchant sale total will exceed the credit cap with current transaction.',
        self::FPSF_DECLINED_BY_FILTERS => 'Fraud Protection Services Filter — Declined by filters',
        self::FPSF_FLAGGED_FOR_REVIEW_BY_FILTERS => 'Fraud Protection Services Filter — Flagged for review by filters',
        self::FPSF_NOT_PROCESSED_BY_FILTERS => 'Fraud Protection Services Filter — Not processed by filters',
        self::FPSF_DECLINED_BY_MERCHANT_AFTER_BEING_FLAGGED_FOR_REVIEW_BY_FILTERS =>
            'Fraud Protection Services Filter — Declined by merchant after being flagged for review by filters',
        self::CARD_HAS_NOT_BEEN_SUBMITTED_FOR_UPDATE => 'Card has not been submitted for update',
        self::DATA_MISMATCH_IN_HTTP_RETRY_REQUEST => 'Data mismatch in HTTP retry request',
        self::ISSUING_BANK_TIMED_OUT => 'Issuing bank timed out',
        self::ISSUING_BANK_UNAVAILABLE => 'Issuing bank unavailable',
        self::SECURE_TOKEN_ALREADY_BEEN_USED => 'Secure Token already been used.',
        self::TRANSACTION_USING_SECURE_TOKEN_IS_ALREADY_IN_PROGRESS =>
            'Transaction using secure token is already in progress.',
        self::SECURE_TOKEN_EXPIRED => 'Secure Token Expired.',
        self::REAUTH_ERROR => 'Reauth error',
        self::ORDER_ERROR => 'Order error',
        self::CYBERCASH_BATCH_ERROR => 'Cybercash Batch Error',
        self::CYBERCASH_QUERY_ERROR => 'Cybercash Query Error',
        self::GENERIC_HOST_ERROR => 'Generic host error.',
        self::BUYER_AUTHENTICATION_SERVICE_UNAVAILABLE => 'Buyer Authentication Service unavailable',
        self::BAS_TRANSACTION_TIMEOUT => 'Buyer Authentication Service — Transaction timeout',
        self::BAS_INVALID_CLIENT_VERSION => 'Buyer Authentication Service — Invalid client version',
        self::BAS_INVALID_TIMEOUT_VALUE => 'Buyer Authentication Service — Invalid timeout value',
        self::BUYER_AUTHENTICATION_SERVICE_UNAVAILABLE_1011 => 'Buyer Authentication Service unavailable',
        self::BUYER_AUTHENTICATION_SERVICE_UNAVAILABLE_1012 => 'Buyer Authentication Service unavailable',
        self::BUYER_AUTHENTICATION_SERVICE_UNAVAILABLE_1013 => 'Buyer Authentication Service unavailable',
        self::BAS_MERCHANT_IS_NOT_ENROLLED_FOR_BUYER_AUTHENTICATION_SERVICE_3D_SECURE =>
            'Buyer Authentication Service — Merchant is not enrolled for Buyer Authentication Service (3-D Secure)',
        self::BAS_3_D_SECURE_ERROR_RESPONSE_RECEIVED =>
            'Buyer Authentication Service — 3-D Secure error response received.',
        self::BAS_3_D_SECURE_ERROR_RESPONSE_IS_INVALID =>
            'Buyer Authentication Service — 3-D Secure error response is invalid.',
        self::BAS_INVALID_CARD_TYPE => 'Buyer Authentication Service — Invalid card type',
        self::BAS_INVALID_OR_MISSING_CURRENCY_CODE => 'Buyer Authentication Service — Invalid or missing currency code',
        self::BAS_MERCHANT_STATUS_FOR_3D_SECURE_IS_INVALID =>
            'Buyer Authentication Service — merchant status for 3D secure is invalid',
        self::BAS_VAF_MISSING_OR_INVALID_PARES =>
            'Buyer Authentication Service — Validate Authentication failed: missing or invalid PARES',
        self::BAS_VAF_PARES_FORMAT_IS_INVALID =>
            'Buyer Authentication Service — Validate Authentication failed: PARES format is invalid',
        self::BAS_VAF_CANNOT_FIND_SUCCESSFUL_VERIFY_ENROLLMENT =>
            'Buyer Authentication Service — Validate Authentication failed: Cannot find successful Verify Enrollment',
        self::BAS_VAF_SIGNATURE_VALIDATION_FAILED_FOR_PARES =>
            'Buyer Authentication Service — Validate Authentication failed: Signature validation failed for PARES',
        self::BAS_VAF_MISMATCHED_OR_INVALID_AMOUNT_IN_PARES =>
            'Buyer Authentication Service — Validate Authentication failed: Mismatched or invalid amount in PARES',
        self::BAS_VAF_MISMATCHED_OR_INVALID_ACQUIRER_IN_PARES =>
            'Buyer Authentication Service — Validate Authentication failed: Mismatched or invalid acquirer in PARES',
        self::BAS_VAF_MISMATCHED_OR_INVALID_MERCHANT_ID_IN_PARES =>
            'Buyer Authentication Service — Validate Authentication failed: Mismatched or invalid Merchant ID in PARES',
        self::BAS_VAF_MISMATCHED_OR_INVALID_CARD_NUMBER_IN_PARES =>
            'Buyer Authentication Service — Validate Authentication failed: Mismatched or invalid card number in PARES',
        self::BAS_VAF_MISMATCHED_OR_INVALID_CURRENCY_CODE_IN_PARES =>
            'Buyer Authentication Service — Validate Authentication failed:
            Mismatched or invalid currency code in PARES',
        self::BAS_VAF_MISMATCHED_OR_INVALID_XID_IN_PARES =>
            'Buyer Authentication Service — Validate Authentication failed: Mismatched or invalid XID in PARES',
        self::BAS_VAF_MISMATCHED_OR_INVALID_ORDER_DATE_IN_PARES =>
            'Buyer Authentication Service — Validate Authentication failed: Mismatched or invalid order date in PARES',
        self::BAS_VAF_THIS_PARES_WAS_ALREADY_VALIDATED_FOR_A_PREVIOUS_VALIDATE_AUTHENTICATION_TRANSACTION =>
            'Buyer Authentication Service — Validate Authentication failed:
            This PARES was already validated for a previous Validate Authentication transaction',
    ];

    /**
     * @param string $status
     * @return string
     */
    public static function getMessage($status)
    {
        if (!array_key_exists($status, static::$messages)) {
            throw new \InvalidArgumentException('Not supported response status code');
        }

        return static::$messages[$status];
    }
}

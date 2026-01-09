<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Response;

/**
 * Maps PayPal Payflow communication error codes to human-readable messages.
 *
 * Provides mapping of PayPal Payflow API communication error codes to descriptive
 * messages for network and connection issue interpretation.
 */
class CommunicationErrorsStatusMap
{
    public const FAILED_TO_CONNECT_TO_HOST = '-1';
    public const FAILED_TO_RESOLVE_HOSTNAME = '-2';
    public const FAILED_TO_INITIALIZE_SSL_CONTEXT = '-5';
    public const AMP_IN_NAME = '-6';
    public const INVALID_NAME_LENGTH = '-7';
    public const SSL_FAILED_TO_CONNECT_TO_HOST = '-8';
    public const SSL_READ_FAILED = '-9';
    public const SSL_WRITE_FAILED = '-10';
    public const PROXY_AUTHORIZATION_FAILED = '-11';
    public const TIMEOUT_WAITING_FOR_RESPONSE = '-12';
    public const SELECT_FAILURE = '-13';
    public const TOO_MANY_CONNECTIONS = '-14';
    public const FAILED_TO_SET_SOCKET_OPTIONS = '-15';
    public const PROXY_READ_FAILED = '-20';
    public const PROXY_WRITE_FAILED = '-21';
    public const FAILED_TO_INITIALIZE_SSL_CERTIFICATE = '-22';
    public const HOST_ADDRESS_NOT_SPECIFIED = '-23';
    public const INVALID_TRANSACTION_TYPE = '-24';
    public const FAILED_TO_CREATE_A_SOCKET = '-25';
    public const FAILED_TO_INITIALIZE_SOCKET_LAYER = '-26';
    public const INVALID_NAME_LENGTH_CLAUSE = '-27';
    public const PARAMETER_LIST_FORMAT_ERROR_NAME = '-28';
    public const FAILED_TO_INITIALIZE_SSL_CONNECTION = '-29';
    public const INVALID_TIMEOUT_VALUE = '-30';
    public const THE_CERTIFICATE_CHAIN_DID_NOT_VALIDATE_NO_LOCAL_CERTIFICATE_FOUND = '-31';
    public const THE_CERTIFICATE_CHAIN_DID_NOT_VALIDATE_COMMON_NAME_DID_NOT_MATCH_URL = '-32';
    public const UNEXPECTED_REQUEST_ID_FOUND_IN_REQUEST = '-40';
    public const REQUIRED_REQUEST_ID_NOT_FOUND_IN_REQUEST = '-41';
    public const OUT_OF_MEMORY = '-99';
    public const PARAMETER_LIST_CANNOT_BE_EMPTY = '-100';
    public const CONTEXT_INITIALIZATION_FAILED = '-103';
    public const UNEXPECTED_TRANSACTION_STATE = '-104';
    public const INVALID_NAME_VALUE_PAIR_REQUEST = '-105';
    public const INVALID_RESPONSE_FORMAT = '-106';
    public const THIS_XMLPAY_VERSION_IS_NOT_SUPPORTED = '-107';
    public const THE_SERVER_CERTIFICATE_CHAIN_DID_NOT_VALIDATE = '-108';
    public const UNABLE_TO_DO_LOGGING = '-109';
    public const ERROR_OCCURRED_WHILE_INITIALIZING_FROM_MESSAGE_FILE = '-111';
    public const UNABLE_TO_ROUND_AND_TRUNCATE_THE_CURRENCY_VALUE_SIMULTANEOUSLY = '-113';

    /**
     * @var array
     */
    protected static $messages = [
        self::FAILED_TO_CONNECT_TO_HOST => 'Failed to connect to host',
        self::FAILED_TO_RESOLVE_HOSTNAME => 'Failed to resolve hostname',
        self::FAILED_TO_INITIALIZE_SSL_CONTEXT => 'Failed to initialize SSL context',
        self::AMP_IN_NAME => 'Parameter list format error: & in name',
        self::INVALID_NAME_LENGTH => 'Parameter list format error: invalid name length clause',
        self::SSL_FAILED_TO_CONNECT_TO_HOST => 'SSL failed to connect to host',
        self::SSL_READ_FAILED => 'SSL read failed',
        self::SSL_WRITE_FAILED => 'SSL write failed',
        self::PROXY_AUTHORIZATION_FAILED => 'Proxy authorization failed',
        self::TIMEOUT_WAITING_FOR_RESPONSE => 'Timeout waiting for response',
        self::SELECT_FAILURE => 'Select failure',
        self::TOO_MANY_CONNECTIONS => 'Too many connections',
        self::FAILED_TO_SET_SOCKET_OPTIONS => 'Failed to set socket options',
        self::PROXY_READ_FAILED => 'Proxy read failed',
        self::PROXY_WRITE_FAILED => 'Proxy write failed',
        self::FAILED_TO_INITIALIZE_SSL_CERTIFICATE => 'Failed to initialize SSL certificate',
        self::HOST_ADDRESS_NOT_SPECIFIED => 'Host address not specified',
        self::INVALID_TRANSACTION_TYPE => 'Invalid transaction type',
        self::FAILED_TO_CREATE_A_SOCKET => 'Failed to create a socket',
        self::FAILED_TO_INITIALIZE_SOCKET_LAYER => 'Failed to initialize socket layer',
        self::INVALID_NAME_LENGTH_CLAUSE =>
            'Parameter list format error: invalid [] name length clause',
        self::PARAMETER_LIST_FORMAT_ERROR_NAME => 'Parameter list format error: name',
        self::FAILED_TO_INITIALIZE_SSL_CONNECTION => 'Failed to initialize SSL connection',
        self::INVALID_TIMEOUT_VALUE => 'Invalid timeout value',
        self::THE_CERTIFICATE_CHAIN_DID_NOT_VALIDATE_NO_LOCAL_CERTIFICATE_FOUND =>
            'The certificate chain did not validate, no local certificate found',
        self::THE_CERTIFICATE_CHAIN_DID_NOT_VALIDATE_COMMON_NAME_DID_NOT_MATCH_URL =>
            'The certificate chain did not validate, common name did not match URL',
        self::UNEXPECTED_REQUEST_ID_FOUND_IN_REQUEST => 'Unexpected Request ID found in request',
        self::REQUIRED_REQUEST_ID_NOT_FOUND_IN_REQUEST => 'Required Request ID not found in request',
        self::OUT_OF_MEMORY => 'Out of memory',
        self::PARAMETER_LIST_CANNOT_BE_EMPTY => 'Parameter list cannot be empty',
        self::CONTEXT_INITIALIZATION_FAILED => 'Context initialization failed',
        self::UNEXPECTED_TRANSACTION_STATE => 'Unexpected transaction state',
        self::INVALID_NAME_VALUE_PAIR_REQUEST => 'Invalid name value pair request',
        self::INVALID_RESPONSE_FORMAT => 'Invalid response format',
        self::THIS_XMLPAY_VERSION_IS_NOT_SUPPORTED => 'This XMLPay version is not supported',
        self::THE_SERVER_CERTIFICATE_CHAIN_DID_NOT_VALIDATE => 'The server certificate chain did not validate',
        self::UNABLE_TO_DO_LOGGING => 'Unable to do logging',
        self::ERROR_OCCURRED_WHILE_INITIALIZING_FROM_MESSAGE_FILE =>
            'Error occurred while initializing from message file',
        self::UNABLE_TO_ROUND_AND_TRUNCATE_THE_CURRENCY_VALUE_SIMULTANEOUSLY =>
            'Unable to round and truncate the currency value simultaneously',
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

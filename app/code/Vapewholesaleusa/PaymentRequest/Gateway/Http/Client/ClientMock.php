<?php

declare(strict_types=1);

namespace Vapewholesaleusa\PaymentRequest\Gateway\Http\Client;

class ClientMock extends \MageWorx\OrderEditor\Gateway\Http\Client\ClientMock
{
    public const SUCCESS = 1;
    public const FAILURE = 0;

    /**
     * @var array
     */
    private $results = [
        self::SUCCESS,
        self::FAILURE
    ];

    /**
     * @param \Magento\Payment\Model\Method\Logger $logger
     */
    public function __construct(
        private readonly \Magento\Payment\Model\Method\Logger $logger
    ) {
        parent::__construct($logger);
    }

    /**
     * Places a request to the payment gateway and logs the request and response data.
     *
     * @param \Magento\Payment\Gateway\Http\TransferInterface $transferObject The transfer data for the request.
     * @return array The generated response from the payment gateway.
     * @throws \Exception
     */
    public function placeRequest(\Magento\Payment\Gateway\Http\TransferInterface $transferObject)
    {
        $response = $this->generateResponse($transferObject);

        // Log the request and response data
        $this->logger->debug(
            [
                'request'  => $transferObject->getBody(),
                'response' => $response
            ]
        );

        return $response;
    }

    /**
     * Returns response fields for result code
     *
     * @param int $resultCode
     * @return array
     */
    private function getFieldsBasedOnResponseType($resultCode)
    {
        return match ($resultCode) {
            self::FAILURE => [
                'FRAUD_MSG_LIST' => [
                    'Stolen card',
                    'Customer location differs'
                ]
            ],
            default => [],
        };
    }

    /**
     * Generates a response with the result code and transaction ID.
     *
     * Uses existing `TXN_ID` from the transfer data, or generates a new one if absent.
     *
     * @param \Magento\Payment\Gateway\Http\TransferInterface $transfer
     * @return array Response data with result code, TXN_ID, and additional fields.
     * @throws \Exception
     */
    protected function generateResponse(\Magento\Payment\Gateway\Http\TransferInterface $transfer)
    {
        if (isset($transfer->getBody()['TXN_ID']) && !empty($transfer->getBody()['TXN_ID'])) {
            return array_merge(
                [
                    'RESULT_CODE' => $this->results[0],
                    'TXN_ID'      => $transfer->getBody()['TXN_ID']
                ],
                $this->getFieldsBasedOnResponseType($this->results[0])
            );
        } else {
            return array_merge(
                [
                    'RESULT_CODE' => $this->results[0],
                    'TXN_ID'      => $this->generateTxnId()
                ],
                $this->getFieldsBasedOnResponseType($this->results[0])
            );
        }
    }
}

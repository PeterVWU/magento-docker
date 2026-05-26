<?php

declare(strict_types=1);

namespace Vapewholesaleusa\PaymentRequest\Service;

class Payment
{
    /**
     * @param \Vapewholesaleusa\PaymentRequest\Model\Config $config
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Url $urlBuilder
     */
    public function __construct(
        private readonly \Vapewholesaleusa\PaymentRequest\Model\Config $config,
        private readonly \Psr\Log\LoggerInterface                      $logger,
        private readonly \Magento\Framework\Url                        $urlBuilder
    ) {
    }

    /**
     * Generates a hosted payment page link for the order.
     *
     * @param \Magento\Sales\Model\Order $order The order for which the payment link is generated.
     * @return string The generated payment token.
     *
     * @throws \Exception If an error occurs during the request.
     */
    public function generateHostedPaymentPage(\Magento\Sales\Model\Order $order, \Magento\Sales\Model\Order\Invoice $invoice): string
    {
        try {
            $xmlData = $this->buildXmlHostedPaymentPageRequest($order, $invoice);
            $response = $this->sendXmlRequest($xmlData);
            if ($response['messages']['resultCode'] === 'Ok') {
                return $response['token'];
            } else {
                throw new \Exception("Error generating payment link: " . $response['messages']['message']['text']);
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * Retrieves transaction details from Authorize.net by sending a request with the given transaction ID.
     *
     * @param string $transactionId The ID of the transaction to retrieve details for.
     * @return mixed The response from Authorize.net containing transaction details.
     * @throws \Exception If there is an error during the request process.
     */
    public function getTransactionDetails(string $transactionId)
    {
        try {
            // Build the XML request for transaction details
            $xmlData = $this->buildXmlTransactionDetailsRequest($transactionId);

            // Send the XML request and return the response
            $response = $this->sendXmlRequest($xmlData);

            if ($response['messages']['resultCode'] === 'Ok') {
                return $response['transaction'];
            } else {
                throw new \Exception("Error generating transaction: " . $response['messages']['message']['text']);
            }
        } catch (\Exception $exception) {
            // Log the error and rethrow the exception
            $this->logger->error($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * Builds the XML request for the hosted payment page.
     *
     * @param \Magento\Sales\Model\Order $order The order for which the XML request is generated.
     * @param \Magento\Sales\Model\Order\Invoice $invoice The invoice for which the XML request is generated.
     * @return string The generated XML request.
     */
    private function buildXmlHostedPaymentPageRequest($order, $invoice): string
    {
        $merchantLogin = $this->config->getApiLoginId();
        $transactionKey = $this->config->getTransactionKey();

        $xmlData = '<?xml version="1.0" encoding="utf-8"?>';
        $xmlData .= '<getHostedPaymentPageRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">';
        $xmlData .= '<merchantAuthentication>';
        $xmlData .= '<name>' . $merchantLogin . '</name>';
        $xmlData .= '<transactionKey>' . $transactionKey . '</transactionKey>';
        $xmlData .= '</merchantAuthentication>';
        $xmlData .= '<transactionRequest>';
        $xmlData .= '<transactionType>authCaptureTransaction</transactionType>';
        $xmlData .= '<amount>' . $invoice->getGrandTotal() . '</amount>';
        $xmlData .= '<order>';
        $xmlData .= '<invoiceNumber>' . $this->getInvoiceNumber($invoice) . '</invoiceNumber>';
        $xmlData .= '<description>Payment Request</description>';
        $xmlData .= '</order>';
        $xmlData .= '<poNumber>'. $this->getOrderNumber($order) .'</poNumber>';
        $xmlData .= '<customer>';
        $xmlData .= '<email>' . $order->getCustomerEmail() . '</email>';
        $xmlData .= '</customer>';
        $xmlData .= '<billTo>';
        $xmlData .= '<firstName>' . $order->getCustomerFirstname() . '</firstName>';
        $xmlData .= '<lastName>' . $order->getCustomerLastname() . '</lastName>';
        $xmlData .= '<address>' . $order->getBillingAddress()->getStreetLine(1) . '</address>';
        $xmlData .= '<city>' . $order->getBillingAddress()->getCity() . '</city>';
        $xmlData .= '<state>' . $order->getBillingAddress()->getRegion() . '</state>';
        $xmlData .= '<zip>' . $order->getBillingAddress()->getPostcode() . '</zip>';
        $xmlData .= '<country>' . $order->getBillingAddress()->getCountryId() . '</country>';
        $xmlData .= '</billTo>';
        $xmlData .= '</transactionRequest>';
        $xmlData .= '<hostedPaymentSettings>';

        $xmlData .= '<setting><settingName>hostedPaymentReturnOptions</settingName>';
        $xmlData .= '<settingValue>{"showReceipt": true, "url": "'.
            $this->getReceiptUrl() .'", "cancelUrl": "'.
            $this->getCancelUrl() .'"}</settingValue>';
        $xmlData .= '</setting>';

        $xmlData .= '<setting><settingName>hostedPaymentPaymentOptions</settingName>';
        $xmlData .= '<settingValue>{"cardCodeRequired": false, "showCreditCard": true, "showBankAccount": false}</settingValue>';
        $xmlData .= '</setting>';

        $xmlData .= '<setting><settingName>hostedPaymentStyleOptions</settingName>';
        $xmlData .= '<settingValue>{"bgColor": "'.
            $this->config->getStyleColor() .'"}</settingValue>';
        $xmlData .= '</setting>';

        $xmlData .= '<setting><settingName>hostedPaymentOrderOptions</settingName>';
        $xmlData .= '<settingValue>{"show": true, "merchantName": "'.
            $this->config->getMerchantName() .'"}</settingValue>';
        $xmlData .= '</setting>';

        $xmlData .= '</hostedPaymentSettings>';
        $xmlData .= '</getHostedPaymentPageRequest>';

        return $xmlData;
    }

    /**
     * Builds the XML request to retrieve transaction details from Authorize.net using the transaction ID.
     *
     * @param string $transactionId The ID of the transaction to retrieve.
     * @return string The XML string for the getTransactionDetailsRequest.
     */
    private function buildXmlTransactionDetailsRequest(string $transactionId): string
    {
        // Retrieve merchant authentication credentials from configuration
        $merchantLogin = $this->config->getApiLoginId();
        $transactionKey = $this->config->getTransactionKey();

        // Build the XML request
        $xmlData = '<?xml version="1.0" encoding="utf-8"?>';
        $xmlData .= '<getTransactionDetailsRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">';
        $xmlData .= '<merchantAuthentication>';
        $xmlData .= '<name>' . $merchantLogin . '</name>';
        $xmlData .= '<transactionKey>' . $transactionKey . '</transactionKey>';
        $xmlData .= '</merchantAuthentication>';
        $xmlData .= '<transId>' . $transactionId . '</transId>';
        $xmlData .= '</getTransactionDetailsRequest>';

        return $xmlData;
    }

    /**
     * Sends the XML request to the payment gateway.
     *
     * @param string $xmlData The XML data to send.
     * @return array The response from the gateway as an array.
     *
     * @throws \Exception If there is a cURL error or an XML parsing error.
     */
    private function sendXmlRequest(string $xmlData): array
    {
        $postUrl = $this->config->getGatewayUrl();

        $header = [
            "POST /AUTHORIZE HTTP/1.0",
            "MIME-Version: 1.0",
            "Content-type: text/xml",
            "Content-length: " . strlen($xmlData),
            "Request-number: 1",
            "Document-type: Request",
            "Interface-Version: 0.3"
        ];

        $ch = curl_init($postUrl);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception('cURL Error: ' . curl_error($ch));
        }

        curl_close($ch);

        $new = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOWARNING);
        if (!$new) {
            throw new \Exception("Error simplexml_load_string");
        }

        $con = json_encode($new);
        return json_decode($con, true);
    }

    /**
     * Returns the base URL for the cancel action.
     *
     * @return string The base URL.
     */
    private function getCancelUrl(): string
    {
        return $this->urlBuilder->getBaseUrl();
    }

    /**
     * Returns the base URL for the receipt action.
     *
     * @return string The base URL.
     */
    private function getReceiptUrl(): string
    {
        return $this->urlBuilder->getBaseUrl();
    }

    /**
     * Retrieves the increment ID of the given invoice.
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice The invoice object.
     * @return string The increment ID of the invoice.
     */
    private function getInvoiceNumber($invoice)
    {
        return $invoice->getIncrementId();
    }

    /**
     * Retrieves the increment ID of the given order.
     *
     * @param \Magento\Sales\Model\Order $order The order object.
     * @return string The increment ID of the invoice.
     */
    private function getOrderNumber($order)
    {
        return $order->getIncrementId();
    }
}

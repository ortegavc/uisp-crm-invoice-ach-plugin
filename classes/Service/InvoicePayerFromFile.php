<?php

declare(strict_types=1);

namespace App\Service;

use App\Nacha\File;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;

class InvoicePayerFromFile
{
    private $api = null;
    private $invoiceToUpdate;
    private $unpaidInvoices = [];
    const CLIENT_ATTRIBUTE_ROUTING_NUMBER = 'routingNumber';
    const CLIENT_ATTRIBUTE_BANK_ACCOUNT_NAME = 'bankAccountNumber';
    const CLIENT_TAG_ACH = 'ACH';

    const INVOICE_STATUS_DRAFT = 0;
    const INVOICE_STATUS_UNPAID = 1;
    const INVOICE_STATUS_PARTIALLY_PAID = 2;
    const INVOICE_STATUS_PAID = 3;
    const INVOICE_STATUS_VOID = 4;
    const INVOICE_STATUS_PROCESSED_PROFORMA = 5;

    public function __construct($invoiceToUpdate, UcrmApi $api)
    {
        $this->invoiceToUpdate = $invoiceToUpdate;

        $this->api = $api;
    }

    private function getPaymentMethod()
    {
        $paymentMethods = $this->api->get('payment-methods');
        $paymentMethod = '6efe0fa8-36b2-4dd1-b049-427bffc7d369';

        foreach ($paymentMethods as $method) {
            if ($method['name'] == 'Custom') {
                $paymentMethod = $method['id'];
                break;
            }
        }

        return $paymentMethod;
    }

    public function payInvoices()
    {
        $result = [
            'invoices' => $this->unpaidInvoices,
            'successful' => [],
            'failures' => [],
            'successfulIds' => [],
            'failuresIds' => []
        ];

        $paymentMethod = $this->getPaymentMethod();

        foreach ($this->unpaidInvoices as $invoice) {
            $invoiceId = $invoice['id'];
            $invoiceNum = $invoice['number'];

            try {
                $this->api->post('payments', [
                    "methodId" => $paymentMethod,
                    "currencyCode" => $invoice['currencyCode'],
                    "attributes" => [],
                    "applyToInvoicesAutomatically" => true,
                    "invoiceIds" => [$invoiceId],
                    "clientId" => $invoice['clientId'],
                    "amount" => $invoice['amountToPay'],
                    "note" => "Paid via api",
                    "providerName" => "Custom",
                    "providerPaymentId" => "WP000000",
                    "providerPaymentTime" => $invoice['createdDate']
                ]);

                $result['successfulIds'][] = $invoiceId;
                $result['successfulNumbers'][] = $invoiceNum;
                $result['successful'][] = "Invoice № {$invoiceNum} payed successfully";
            } catch (\Exception $err) {
                $result['failuresIds'][] = $invoiceId;
                $result['failuresNumbers'][] = $invoiceNum;
                $result['failures'][] = "Error, when trying to pay invoice № {$invoiceNum} " . $err->getMessage();
            }
        }

        if (count($result['successfulIds']) == count($this->unpaidInvoices)) {
            FileStorage::removeById($this->invoiceToUpdate['id']);
            return $result;
        }

        if (count($result['successfulIds']) == 0) {
            return $result;
        }

        if (count($this->unpaidInvoices) > 0) {
            $data = FileStorage::getById($this->invoiceToUpdate['id']);

            if (is_null($data)) {
                $result['failures'][] = "Cant get data from file by id";
                return $result;
            }

            FileStorage::updateInvoicesById($this->invoiceToUpdate['id'], $result['failuresIds']);
        }

        return $result;
    }

    public function loadUnpaidInvoices()
    {
        $this->unpaidInvoices = [];
        $clientInfoCache = [];

        foreach ($this->invoiceToUpdate['invoices'] as $invoiceId) {
            $invoice = null;
            $clientId = '';

            try {
                $invoice = $this->api->get('invoices/' . $invoiceId);

                if ($invoice['status'] == self::INVOICE_STATUS_PAID) {
                    continue;
                }

                $clientId = (string)$invoice['clientId'];

                if (!array_key_exists($clientId, $clientInfoCache)) {
                    $clientInfoCache[$clientId] = $this->api->get('clients/' . $clientId);
                }
            } catch (\Exception $err) {
                continue;
            }

            $record = [
                'clientRoutingNumber' => '',
                'clientBankAccountNumber' => ''
            ];

            $isAchTagFounded = false;

            if (
                !empty($clientInfoCache[$clientId]['tags'])
                and is_array($clientInfoCache[$clientId]['tags'])
            ) {
                foreach ($clientInfoCache[$clientId]['tags'] as $tag) {
                    if (strtoupper($tag['name']) == self::CLIENT_TAG_ACH) {
                        $isAchTagFounded = true;
                    }
                }
            }

            if (!$isAchTagFounded) {
                continue;
            }

            if (
                !empty($clientInfoCache[$clientId]['attributes'])
                and is_array($clientInfoCache[$clientId]['attributes'])
            ) {
                foreach ($clientInfoCache[$clientId]['attributes'] as $attribute) {
                    if ($attribute['key'] == self::CLIENT_ATTRIBUTE_ROUTING_NUMBER) {
                        $record['clientRoutingNumber'] = $attribute['value'];
                    } elseif ($attribute['key'] == self::CLIENT_ATTRIBUTE_BANK_ACCOUNT_NAME) {
                        $record['clientBankAccountNumber'] = $attribute['value'];
                    }
                }

                if (
                    empty($record['clientRoutingNumber'])
                    or empty($record['clientBankAccountNumber'])
                ) {
                    continue;
                }

                if (strlen($record['clientRoutingNumber']) != 9) {
                    throw new \Exception(
                        'Client #' . $clientId . ' Routing number: ' .
                        $record['clientRoutingNumber'] . ' should have 9 digits.'
                    );
                }
            } else {
                continue;
            }

            $this->unpaidInvoices[] = $invoice;
        }

        return $this;
    }
}

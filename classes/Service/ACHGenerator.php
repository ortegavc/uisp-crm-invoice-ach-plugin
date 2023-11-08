<?php

declare(strict_types=1);

namespace App\Service;

use App\Nacha\Batch;
use App\Nacha\Field\TransactionCode;
use App\Nacha\File;
use App\Nacha\Record\DebitEntry;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;

class ACHGenerator
{
    private $invoiceSearchParams = [];
    private $unpaidInvoices = [];
    private $api = null;
    private $companyId = 0;
    private $companyName = '';
    private $issueClients = '';

    const CLIENT_ATTRIBUTE_ROUTING_NUMBER = 'routingNumber';
    const CLIENT_ATTRIBUTE_BANK_ACCOUNT_NAME = 'bankAccountNumber';
    const CLIENT_TAG_ACH = 'ACH';

    public function __construct(array $invoiceSearchParams, UcrmApi $api)
    {
        $this->invoiceSearchParams = $invoiceSearchParams;

        $this->api = $api;
    }

    public function generate()
    {
        //Immediate Destination - LaSalle Bank N.A. or Standard Federal Bank’s transit routing
        //number preceded by a blank. (071000505 for LaSalle and 072000805 for Standard Federal)
        $originatingDFiId = $this->invoiceSearchParams["originatingDFiId"];

        // Enter LaSalle Bank or Standard Federal Bank
        $immediateDestinationName = $this->invoiceSearchParams["immediateDestinationName"];

        if (
            empty($originatingDFiId)
            or empty($immediateDestinationName)
            or empty($this->companyId)
            or empty($this->companyName)
        ) {
            throw new \Exception('No bank information from organization');
        }

        $originatingDFiIdCropped = (string) substr($originatingDFiId, 0, 8);

        $file = new File();
        $file->getHeader()
            ->setPriorityCode('1')

            //            Enter LaSalle’s routing transit number 07100050, or
            //            Standard Federal’s transit routing number of 07200080.
            ->setImmediateDestination($originatingDFiId)
            ->setImmediateOrigin($this->companyId)
            ->setFileCreationDate(date('ymd'))
            ->setFileCreationTime(date('Hi'))
            ->setFormatCode('1')
            ->setImmediateDestinationName($immediateDestinationName)
            ->setImmediateOriginName($this->companyName);

        $batch = new Batch();
        $batch->getHeader()
            ->setServiceClassCode(200)
            ->setCompanyName($this->companyName)
            ->setCompanyDiscretionaryData('MONTHLY SUBSCRIPTION')
            ->setCompanyId($this->companyId)
            ->setStandardEntryClassCode('PPD')
            ->setCompanyEntryDescription('SUBSCRIPTION')
            ->setCompanyDescriptiveDate(date('M d'))
            ->setEffectiveEntryDate(date('ymd'))
            ->setOriginatorStatusCode('1')
            ->setOriginatingDFiId($originatingDFiIdCropped);

        $number = 1;

        $exportedInvoiceIds = [];

        foreach ($this->unpaidInvoices as $invoice) {
            $clientId = $invoice['clientId'];
            $exportedInvoiceIds[] = $invoice['invoiceId'];

            $routingNumber = $invoice['clientRoutingNumber'];

            // Receiving DFI Identification
            // Transit routing number of the receiver’s financial institution.
            $receivingDfiId = (string) substr($routingNumber, 0, 8);

            // Check Digit
            // The ninth digits of the receiving financial institutions transit routing number
            $checkDigit = (string) substr($routingNumber, -1);

            // DFI Account Number
            // Receiver’s account number at their financial
            // institution. Left justify.
            $clientBankAccountNumber = $invoice['clientBankAccountNumber'];

            $entry = (new DebitEntry())
                ->setTransactionCode(TransactionCode::CHECKING_DEBIT)
                ->setReceivingDfiId($receivingDfiId)
                ->setCheckDigit($checkDigit)
                ->setDFiAccountNumber($clientBankAccountNumber)
                ->setAmount($invoice['amountToPay'])
                ->setIndividualId($clientId)
                ->setIdividualName($invoice['clientFirstName'] . ' ' . $invoice['clientLastName'])
                ->setAddendaRecordIndicator(0)
                ->setTraceNumber($originatingDFiIdCropped, $number);

            $batch->addEntry($entry);

            $number++;
        }

        $file->addBatch($batch);

        try {
            FileStorage::appendInvoices($exportedInvoiceIds);
        } catch (\Exception $err) {
            echo '<pre>';
            var_dump($err->getMessage());
            echo '</pre>';
        }

        return (string) $file;
    }

    public function prepareInformation()
    {
        if (count($this->unpaidInvoices) > 0) {
            return $this->unpaidInvoices;
        }

        $unpaidInvoices = $this->api->get('invoices', $this->invoiceSearchParams);

        $this->unpaidInvoices = [];
        $clientInfoCache = [];

        foreach ($unpaidInvoices as $i => $invoice) {
            $clientId = (string)$invoice['clientId'];

            if (!array_key_exists($clientId, $clientInfoCache)) {
                $clientInfoCache[$clientId] = $this->api->get('clients/' . $clientId);
            }

            $record = [
                'invoiceId' => $invoice['id'],
                'clientId' => (string) $clientId,
                'amountToPay' => (string) $invoice['amountToPay'],
                'clientFirstName' => (string) $invoice['clientFirstName'],
                'clientLastName' => (string) $invoice['clientLastName'],
                'clientRoutingNumber' => '',
                'clientBankAccountNumber' => '',
            ];

            if (
                empty($record['clientFirstName']) and
                empty($record['clientLastName'])
            ) {
                $record['clientFirstName'] = (string) $invoice['clientCompanyName'];
            }

            if (empty($this->companyId)) {
                $this->companyId = (string) $clientInfoCache[$clientId]['organizationId'];
                $this->companyName = (string) $clientInfoCache[$clientId]['organizationName'];
            }

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

                if (strlen($record['clientRoutingNumber']) != 9) {
                    $this->issueClients .= sprintf('<li><a href="/crm/client/%d/edit" target="blank">Client #%d</a> %s %s | Bank Acc.: %s | Routing No.: %s </li>', $clientId, $clientId, $invoice['clientFirstName'], $invoice['clientLastName'], $record['clientRoutingNumber'], $record['clientBankAccountNumber']);
                    continue;
                }
            } else {
                $this->issueClients .= sprintf('<li><a href="/crm/client/%d/edit" target="blank">Client #%d</a> %s %s (Bank Account/Routing Number missing)</li>', $clientId, $clientId, $invoice['clientFirstName'], $invoice['clientLastName']);
                continue;
            }

            $this->unpaidInvoices[] = $record;
        }

        if ($this->issueClients !== '') {
            throw new \Exception($this->issueClients);
        }

        return $this;
    }

    public function getUnpaidInvoicesCount()
    {
        return count($this->unpaidInvoices);
    }
}

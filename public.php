<?php

declare(strict_types=1);

use App\Service\ACHGenerator;
use App\Service\TemplateRenderer;
use Ubnt\UcrmPluginSdk\Security\PermissionNames;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;
use Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager;
use Ubnt\UcrmPluginSdk\Service\UcrmSecurity;
use App\Service\FileStorage;

$originalErrorReporting = error_reporting();  // Store the original error reporting level
error_reporting(0);  // Disable warnings

chdir(__DIR__);

require __DIR__ . '/vendor/autoload.php';

// Retrieve API connection.
$api = UcrmApi::create();

// Ensure that user is logged in and has permission to view invoices.
$security = UcrmSecurity::create();
$user = $security->getUser();

if (!$user || $user->isClient || !$user->hasViewPermission(PermissionNames::BILLING_INVOICES)) {
    \App\Http::forbidden();
}

$parameters = [];
$message = [];
$isError = false;
$template = __DIR__ . '/templates/form.php';
$isShowNextPage = false;
$additionalInfo = '';
$successHeader = 'Update invoice statuses';
$invoicesCount = 0;
$searchParams = '';
$fileStorage = FileStorage::read();

// Process submitted form.
if (
    array_key_exists('organization', $_REQUEST)
    && array_key_exists('since', $_REQUEST)
    && array_key_exists('until', $_REQUEST)
) {
    $parameters = [
        'organizationId' => $_REQUEST['organization'],
        'createdDateFrom' => $_REQUEST['since'],
        'createdDateTo' => $_REQUEST['until']
    ];

    // make sure the dates are in YYYY-MM-DD format
    if ($parameters['createdDateFrom']) {
        $parameters['createdDateFrom'] = new \DateTimeImmutable($parameters['createdDateFrom']);
        $parameters['createdDateFrom'] = $parameters['createdDateFrom']->format('Y-m-d');
    }
    if ($parameters['createdDateTo']) {
        $parameters['createdDateTo'] = new \DateTimeImmutable($parameters['createdDateTo']);
        $parameters['createdDateTo'] = $parameters['createdDateTo']->format('Y-m-d');
    }

    $parameters['statuses'] = [
        \App\Service\InvoiceStatuses::UNPAID
    ];
}

if (isSelectInvoices()) {
    $achGenerator = new ACHGenerator($parameters, $api);

    try {
        $invoicesCount = $achGenerator->prepareInformation()->getUnpaidInvoicesCount();

        if ($invoicesCount > 0) {
            $isShowNextPage = true;
        } else {
            $isError = true;
            $message = 'No unpaid invoices found';
            $template = __DIR__ . '/templates/success.php';
        }
    } catch (\Exception $err) {
        $isError = true;
        $template = __DIR__ . '/templates/success.php';
        $message = $err->getMessage();
    }
} elseif (isExportJson()) {
    header("Content-Type: application/octet-stream");
    header("Content-disposition: attachment; filename=" . FileStorage::FILENAME);
    echo \json_encode($fileStorage);
    exit;
} elseif (isImportJson()) {
    if (
        isset($_FILES['userfile'])
        and isset($_FILES['userfile']['type'])
        and $_FILES['userfile']['type'] == 'application/json'
    ) {
        $content = file_get_contents($_FILES['userfile']['tmp_name']);

        if (!empty($content)) {
            FileStorage::importFile($content);

            $template = __DIR__ . '/templates/success.php';
            $message = 'File imported successfully';
            $successHeader = 'Import json file';
        }
    } else {
        $isError = true;
        $template = __DIR__ . '/templates/success.php';
        $message = 'No file';
    }
} elseif (isExportToAch()) {
    $parameters['originatingDFiId'] = isset($_REQUEST['originatingDFiId'])
        ? $_REQUEST['originatingDFiId']
        : '';

    $parameters['immediateDestinationName'] = isset($_REQUEST['immediateDestinationName'])
        ? $_REQUEST['immediateDestinationName']
        : '';

    $parameters['searchParams'] = isset($_REQUEST['searchParams'])
        ? $_REQUEST['searchParams']
        : '';

    $achGenerator = new ACHGenerator($parameters, $api);

    try {
        $result = $achGenerator->prepareInformation()->generate();

        if (!empty($result)) {
            header("Content-Type: application/octet-stream");
            header("Content-disposition: attachment; filename=acha_file.txt"); //  TODO: add date to filename
            echo $result;
            exit;
        }
    } catch (\Exception $err) {
        $isError = true;
        $template = __DIR__ . '/templates/success.php';
        $message = $err->getMessage();
    }
} elseif (isUpdateInvoices()) {
    $selectedInvoiceId = isset($_REQUEST['invoicesRowId']) ? $_REQUEST['invoicesRowId'] : '';

    if (empty($selectedInvoiceId)) {
        echo 'empty';
        return;
    }

    $invoiceToUpdate = null;

    foreach ($fileStorage as $invoiceRow) {
        if ($invoiceRow['id'] == $selectedInvoiceId) {
            $invoiceToUpdate = $invoiceRow;
            break;
        }
    }

    if (is_null($invoiceToUpdate)) {
        $isError = true;
        $template = __DIR__ . '/templates/success.php';
        $message = 'Unable to find selected invoices';
    } else {
        $invoicePayer = new \App\Service\InvoicePayerFromFile($invoiceToUpdate, $api);

        $result = $invoicePayer->loadUnpaidInvoices()->payInvoices();

        $renderer = new TemplateRenderer();
        $template = __DIR__ . '/templates/success.php';

        if (count($result['invoices']) == 0) {
            $message = 'No unpaid invoices found';
            $isError = true;
        } else {
            $successCount = count($result['successful']);
            $filuresCount = count($result['failures']);
            $failedInvoiceIds = '';

            if ($filuresCount > 0) {
                $failedInvoiceIds = ", {$filuresCount} failed (" . implode(', ', $result['failuresNumbers']) . ")";
            }

            $message = "Report:<br/>{$successCount} invoices processed" . $failedInvoiceIds;

            $additionalInfo = '';

            if ($successCount > 0) {
                $additionalInfo = '<br/><br/><span style="color: green;font-weight: bold;">Successful:</span><br/>' . implode('<br/>', $result['successful']);
            }

            if ($filuresCount > 0) {
                $additionalInfo .= '<br/><br/><span style="color: red;">Failures:</span><br/>' . implode('<br/>', $result['failures']);
            }
        }
    }
}

// Render form.
$organizations = $api->get('organizations');

$optionsManager = UcrmOptionsManager::create();

$renderer = new TemplateRenderer();
$renderer->render(
    $template,
    [
        'organizations' => $organizations,
        'isShowNextPage' => $isShowNextPage,
        'invoicesCount' => $invoicesCount,
        'fileStorage' => $fileStorage,
        'additionalInfo' => $additionalInfo,
        'parameters' => $parameters,
        'successHeader' => $successHeader,
        'msg' => $message,
        'isError' => $isError,
        'ucrmPublicUrl' => $optionsManager->loadOptions()->ucrmPublicUrl,
    ]
);

function isSelectInvoices()
{
    return (array_key_exists('cmd', $_REQUEST) and $_REQUEST['cmd'] == 'select_invoices');
}

function isExportToAch()
{
    return (array_key_exists('cmd', $_REQUEST) and $_REQUEST['cmd'] == 'export_to_ach');
}
function isExportJson()
{
    return (array_key_exists('cmd', $_REQUEST) and $_REQUEST['cmd'] == 'export_json_file');
}
function isImportJson()
{
    return (array_key_exists('cmd', $_REQUEST) and $_REQUEST['cmd'] == 'import_json_file');
}

function isUpdateInvoices()
{
    return (array_key_exists('cmd', $_REQUEST) and $_REQUEST['cmd'] == 'update_invoices');
}

error_reporting($originalErrorReporting);  // Restore the original error reporting level
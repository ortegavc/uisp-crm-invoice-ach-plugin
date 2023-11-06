<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Invoice to ACH export</title>
        <link rel="stylesheet" href="<?php echo rtrim(htmlspecialchars($ucrmPublicUrl, ENT_QUOTES), '/'); ?>/assets/fonts/lato/lato.css">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
        <link rel="stylesheet" href="public/main.css">
    </head>
    <body>
        <div id="header">
            <h1>Unpaid Invoices to ACH export</h1>
        </div>
        <div id="content" class="container container-fluid ml-0 mr-0">
            <?php
                include '_invoice_search_partial.php';

                if ($isShowNextPage) {
                    include '_generate_ach_partial.php';
                } else {
                    include '_export_invoices_partial.php';
                }
            ?>
        </div>
    </body>
</html>

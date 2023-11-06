<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Update invoice statuses</title>
    <link rel="stylesheet" href="<?php echo rtrim(htmlspecialchars($ucrmPublicUrl, ENT_QUOTES), '/'); ?>/assets/fonts/lato/lato.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link rel="stylesheet" href="public/main.css">
</head>
<body>
<div id="header">
    <?php
    if ($isError) {
        echo '<h1>Error</h1>';
    } else {
        echo '<h1>' . $successHeader . '</h1>';
    }
    ?>
</div>
<div id="content" class="container container-fluid ml-0 mr-0">
    <div class="row mb-4">
        <?php
        if ($isError) {
            echo '<div class="alert alert-danger" role="alert">' . $msg . '</div>';
        } else {
            echo '<div class="alert alert-success" role="alert">' . $msg . '</div>';
        }
        ?>
    </div>
    <div class="mb-4">
        <?php
        echo $additionalInfo;
        ?>
    </div>
</div>
</body>
</html>

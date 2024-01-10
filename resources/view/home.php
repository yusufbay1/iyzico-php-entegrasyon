<?php
require_once('vendor/autoload.php');
require_once('app/Controller/IyzicoController.php');

use App\Controller\IyzicoController;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <?php echo IyzicoController::payload() ?>
    <div id="iyzipay-checkout-form" class="responsive"></div>
</body>

</html>

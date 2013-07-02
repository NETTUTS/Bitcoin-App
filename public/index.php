<?php

require "../vendor/autoload.php";

$app = new \Slim\Slim(array(
    "templates.path" => "../views"
));

$app->get('/', function () use ($app) {
    $app->render('home.php');
});

$app->post('/', function () use ($app) {
    $email = $app->request()->post('email');

    if ($email) {
        $token = "API_TOKEN";

        $ch = curl_init("https://bips.me/api/v1/invoice");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $token . ":");
        curl_setopt($ch, CURLOPT_SSLVERSION,3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            "price" => 20,
            "currency" => "USD",
            "item" => "Book PDF",
            "custom" => array(
                "users_email" => $email
            )
        ));
        $invoiceUrl = curl_exec($ch);

        $app->redirect($invoiceUrl);
    }
});

$app->post('/ipn', function () use ($app) {
    //Slim Request object
    $req = $app->request();

    //Get some variables from the request
    $email = $req->post('custom')['email'];
    $transactionKey = $req->post('transaction')['hash'];
    $invoiceHash = $req->post('hash');
    $status = $req->post('status');

    //Hash the transaction key with the secret
    $secret = 'SECRETKEY';
    $hash = hash("sha512", $transactionKey . $secret);

    //Verify it
    if ($invoiceHash === $hash && $status == 1) {
        //Mandrill URL + API key
        $url = "https://mandrillapp.com/api/1.0/messages/send.json";
        $apiKey = "_siD-lqVBPK6SklixRqqYA";

        //Get Email Template
        $view = $app->view();
        $template = $view->fetch("email.php");

        //Message POST data
        $messageData = array(
            "key" => $apiKey,
            "message" => array(
                "html" => $template,
                "subject" => "Thank you for your Purchase :)",
                "from_email" => "demo@email.com",
                "from_name" => "Your Name",
                "to" => array(
                    array(
                        "email" => $email
                    )
                )
            )
        );

        //Send Request
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($messageData));
        curl_setopt($ch, CURLOPT_SSLVERSION,3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
    }
});

$app->run();
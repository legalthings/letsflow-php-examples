#!/usr/bin/env php
<?php

require 'vendor/autoload.php';

use Ramsey\Uuid\Uuid;

$id = '1237288f-8u6f-3edt-8d2d-4f4ffd938vk';
$seed = '<fill in your admin seed>';

$if = new IdentityFactory();
$admin = $if->initiateIdentity($id, $seed);

$initiator = $admin->createNewIdentity($if->initiateIdentity('b8d636dd-2447-4077-b61a-71f2e4b5b7e3', 'the seed of initiator', 'user'));
$recipient = $admin->createNewIdentity($if->initiateIdentity('b8d636dd-2447-4077-b61a-71f2e4b5b7e4', 'the seed of recipient'));

$scenario = $admin->createScenario('handshake', 'scenarios');

$id = Uuid::uuid4()->toString();
$actors = [
    'initiator' => $initiator->id,
    'recipient' => $recipient->id
];
if(!$initiator->initiateProcess($id, $scenario->id, $actors)) {
    echo 'failed';
    return;
}

if(!$initiator->sendResponse($id, 'greet')) {
    echo 'failed';
    return;
}

if(!$recipient->sendResponse($id, 'reply')) {
    echo 'failed';
    return;
}

if(!$initiator->sendResponse($id, 'complete')) {
    echo 'failed';
    return;
}


echo "success\n";

echo json_encode($initiator->getProcessById($id));

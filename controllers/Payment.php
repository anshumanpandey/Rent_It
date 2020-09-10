<?php
require_once __DIR__ . '/../lib/HtmlStringImporter.php';

function renderSuccesPayment($data) {
    echo getHtmlAsString('templates/PaymentSuccess.html');
}

function renderCancelledPayment($data) {
    echo getHtmlAsString('templates/PaymentCancelled.html');
}

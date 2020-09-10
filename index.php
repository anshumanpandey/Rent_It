<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/lib/ErrorHandling.php';
require_once __DIR__ . '/lib/ProtectedRoute.php';
require_once __DIR__ . '/controllers/user.php';
require_once __DIR__ . '/controllers/location.php';
require_once __DIR__ . '/controllers/vehicle.php';
require_once __DIR__ . '/controllers/Bookings.php';
require_once __DIR__ . '/controllers/Documents.php';
require_once __DIR__ . '/controllers/ErrorTrack.php';
require_once __DIR__ . '/controllers/Payment.php';

$JSON_BODY = json_decode(file_get_contents('php://input'), true);

if ($JSON_BODY["module_name"] && $JSON_BODY["module_name"] == "LOGIN") {
    login($JSON_BODY);
    return;
}

if ($JSON_BODY["module_name"] && $JSON_BODY["module_name"] == "EDIT_PROFILE") {
    $ProtectedRoute($editProfile, $JSON_BODY);
    return;
}

if ($JSON_BODY["module_name"] && $JSON_BODY["module_name"] == "VERIFY_OPT") {
    verifyOpt($JSON_BODY);
    return;
}

if ($JSON_BODY["module_name"] && $JSON_BODY["module_name"] == "USER_EXIST") {
    userExist($JSON_BODY);
    return;
}

if ($JSON_BODY["module_name"] && $JSON_BODY["module_name"] == "RESEND_VERIFY_OPT") {
    resendVerifyOpt($JSON_BODY);
    return;
}

if ($JSON_BODY["module_name"] && $JSON_BODY["module_name"] == "VERIFY") {
    verifyProfile($JSON_BODY);
    return;
}

if ($JSON_BODY["module_name"] && $JSON_BODY["module_name"] == "RESEND_VERIFY") {
    resendCode($JSON_BODY);
    return;
}

if ($JSON_BODY["module_name"] && $JSON_BODY["module_name"] == "RESEND_VERIFY_EMAIL") {
    resendEmail($JSON_BODY);
    return;
}

if ($_GET && $_GET["module_name"] && $_GET["module_name"] == "RENDER_VERIFICATION_EMAIL") {
    renderVerificationEmail($_GET);
    return;
}

if ($_GET && $_GET["module_name"] && $_GET["module_name"] == "TWITTER_CALLBACK") {
    twitterCallback($_GET);
    return;
}

if ($JSON_BODY["module_name"] && $JSON_BODY["module_name"] == "LOGIN_WITH_APPLE") {
    loginWithApple($JSON_BODY);
    return;
}


if ($JSON_BODY["module_name"] && $JSON_BODY["module_name"] == "LOGIN_WITH_FACEBOOK") {
    loginWithFacebook($JSON_BODY);
    return;
}

if ($JSON_BODY["module_name"] && $JSON_BODY["module_name"] == "REGISTER") {
    register($JSON_BODY);
    return;
}

if ($JSON_BODY["module_name"] && $JSON_BODY["module_name"] == "RECOVER_PASS") {
    forgotPassword($JSON_BODY);
    return;
}

if ($_GET && $_GET["module_name"] && $_GET["module_name"] == "LOCATION_SEARCH") {
    searchLocation($_GET);
    return;
}

if ($JSON_BODY["module_name"] && $JSON_BODY["module_name"] == "LOGIN_WITH_TWITTER") {
    generateLoginTwitterPage($_GET);
    return;
}

if ($_GET && $_GET["module_name"] && $_GET["module_name"] == "SEARCH_VEHICLE") {
    $ProtectedRoute($searchVehicle, $_GET);
    return;
}

if ($JSON_BODY["module_name"] && $JSON_BODY["module_name"] == "CREATE_BOOKING") {
    $ProtectedRoute($doBooking, $JSON_BODY);
    return;
}

if ($_GET && $_GET["module_name"] && $_GET["module_name"] == "GET_BOOKINGS") {
    $ProtectedRoute($getBookings, $JSON_BODY);
    return;
}

if ($JSON_BODY["module_name"] && $JSON_BODY["module_name"] == "CANCEL_BOOKING") {
    $ProtectedRoute($cancelBooking, $JSON_BODY);
    return;
}

if ($_POST && $_POST["module_name"] && $_POST["module_name"] == "FILE_UPLOAD") {
    $files = [];
    if (is_array($_FILES)) {
        $files = $_FILES;
    }
    $ProtectedRoute($saveFile, array_merge($_POST, $files));
    return;
}

if ($_POST && $_POST["module_name"] && $_POST["module_name"] == "SIGNATURE_UPLOAD") {
    $files = [];
    if (is_array($_FILES)) {
        $files = $_FILES;
    }
    $ProtectedRoute($saveSignature, array_merge($_POST, $files));
    return;
}

if ($JSON_BODY["module_name"] && $JSON_BODY["module_name"] == "GET_FILES") {
    $ProtectedRoute($getDocuments, $_POST);
    return;
}

if ($JSON_BODY["module_name"] && $JSON_BODY["module_name"] == "SEND_CANCEL_CODE") {
    $ProtectedRoute($sendCancelCode, $JSON_BODY);
    return;
}

if ($JSON_BODY["module_name"] && $JSON_BODY["module_name"] == "VERIFY_CANCEL_CODE") {
    $ProtectedRoute($verifyCancelCode, $JSON_BODY);
    return;
}

if ($JSON_BODY["module_name"] && $JSON_BODY["module_name"] == "ERROR_TRACK") {
    sendError($JSON_BODY);
    return;
}

if ($_POST && $_POST["module_name"] && $_POST["module_name"] == "SAVE_DAMAGE_IMAGES") {
    $files = [];
    if (is_array($_FILES)) {
        $files = $_FILES;
    }
    $ProtectedRoute($saveDamageImages, array_merge($_POST, $files));
    return;
}

if ($_GET && $_GET["module_name"] && $_GET["module_name"] == "PAYMENT_SUCCESS") {
    renderSuccesPayment($JSON_BODY);
    return;
}

if ($JSON_BODY["module_name"] && $JSON_BODY["module_name"] == "GENERATE_PDF") {
    $ProtectedRoute($generatePdf, $JSON_BODY);
    return;
}

if ($_GET && $_GET["module_name"] && $_GET["module_name"] == "PAYMENT_CANCELLED") {
    renderCancelledPayment($JSON_BODY);
    return;
}
//deleteUserByEmail("anshuman@gvtechsolution.com");
header('Content-Type: application/json');
echo json_encode(["success" => "Welcome to Right Cars Mobile API!"], JSON_PRETTY_PRINT);
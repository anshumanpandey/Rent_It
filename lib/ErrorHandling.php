<?php

use JsonSchema\Exception\ValidationException;


function log_error( $num, $str, $file, $line, $context = null ){
    log_exception( new ErrorException( $str, 0, $num, $file, $line ) );
}

//Throwable
//Exception
function log_exception( Throwable $e ){
    $message = "Type: " . get_class( $e ) . "; Message: {$e->getMessage()}; File: {$e->getFile()}; Line: {$e->getLine()};";
    file_put_contents( __DIR__ .'/../logs/errors.log', $message . PHP_EOL, FILE_APPEND );

    if (get_class($e) == "ApiError") {
        header('Content-Type: application/json');
        http_response_code($e->getCode());
        echo json_encode($e, JSON_PRETTY_PRINT);
    } else if (get_class($e) == "ParamsError") {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode($e, JSON_PRETTY_PRINT);
    } else {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(["error" => "UNKWON_ERROR"], JSON_PRETTY_PRINT);
    }
    //header( "Location: {$config["error_page"]}" );
   
    exit();
}

function check_for_fatal(){
    $error = error_get_last();
    if ( $error["type"] == E_ERROR )
        log_error( $error["type"], $error["message"], $error["file"], $error["line"] );
}

register_shutdown_function( "check_for_fatal" );
set_error_handler( "log_error" );
set_exception_handler( "log_exception" );

ini_set( "display_errors", "off" );
error_reporting( E_ALL );
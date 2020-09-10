<?php
class ApiError extends Exception implements JsonSerializable
{
    // Redefinir la excepción, por lo que el mensaje no es opcional
    public function __construct($message, $code = 400, $extra = "", Exception $previous = null) {
        // algo de código
    
        // asegúrese de que todo está asignado apropiadamente
        parent::__construct($message, $code, $previous);
    }

    // representación de cadena personalizada del objeto
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message} {$this->extra}\n";
    }

    public function jsonSerialize() {
        return [
            "code" => $this->code,
            "error" => $this->message,
        ];
    }
}
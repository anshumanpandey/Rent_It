<?php
class ParamsError extends Exception implements JsonSerializable
{
    // Redefinir la excepción, por lo que el mensaje no es opcional
    public function __construct($errors, $code = 400) {
        $this->errors = $errors;
    
        // asegúrese de que todo está asignado apropiadamente
        parent::__construct("Params Error", $code);
    }

    // representación de cadena personalizada del objeto
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

    public function jsonSerialize() {
        return [
            "code" => $this->code,
            "error" => implode("\n",$this->errors)
        ];
    }
}
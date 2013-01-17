<?php
function Telecom_Nombre()
{
    return "Claro El Salvador";
} 
function Telecom_Enviar($telefono, $mensaje, $firma){
    //Convertir la IP a HEX
    $ipi = getenv("REMOTE_ADDR");
    $ip = explode('.', $ipi);
    $HEXIP = sprintf('%02x%02x%02x%02x', $ip[0], $ip[1], $ip[2], $ip[3]);

    $headers = 'From: mensajitos@' . LM_DOMINIO . "\n" .
               'Reply-To: via@' . LM_DOMINIO  . "\n";
    $to = $telefono."@sms.claro.com.sv";
    $subject = substr($firma,0,9);	
    $body = $mensaje . ' [' . $HEXIP . ']';
    if (mail($to, $subject, $body, $headers)) {
        return true;
    } else {
        return false;
    }
}
?>
<?php
//DIGICEL, S.A. de C.V.
function Digicel_Nombre() {
    return "Digicel El Salvador";
}

function Digicel_Enviar($telefono,$mensaje,$firma) {
    //Conveierte la dirección IP a Hexadecimal
    //Obtiene la dirección IP del cliente
    $ipDecimal = getenv("REMOTE_ADDR");
    //Separa cada octeto de la direcci�n IP en decimal y lo almacena en un arreglo
    $ipOctetos = explode('.', $ipDecimal);
    //Convierte cada octeto a hexadecimal
    $ipHexadecimal = sprintf('%02x%02x%02x%02x', $ipOctetos[0], $ipOctetos[1], $ipOctetos[2], $ipOctetos[3]);

    $to      = '503'.$telefono.'@digimensajes.com';	  	
    $headers = 'From: mensajitos@' . LM_DOMINIO . "\r\n" .
               'Reply-To: mensajitos@' . LM_DOMINIO . "\r\n";
    $subject = substr($firma,0,9);
    $body    = substr($mensaje,0,110) . ' IP: '.  $ipHexadecimal. '';
	
    if (mail($to, $subject, $body, $headers)) {
        return true;
    } else {
        return false;
    }
	
}
?>
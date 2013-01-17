<?php
/*************************************************************************/
// Opciones
/*************************************************************************/

// Dominio donde estará instalado la aplicación.
define( 'LM_DOMINIO' , 'ejemplo.lan' );

// Plantilla HTML por defecto.
$plantilla = $home . "/plantilla/mensajitos.htm";

// Flood
$limite_flood_num = 60; //Numero maximo de mensajes por $intervalo_flood a un numero.
$limite_flood_ip  = 60; //Numero maximo de mensajes por $intervalo_flood desde 1 ip
$intervalo_flood  = 3600; //Intervalo de flood (en segundos)
$_cuentaIP = 0;
$_cuentaNum = 0;

// Permitir el envío por GET
$EnviaXGet        = false;

// URL donde está instalado el script
$vars["{script}"] = "http://www.ejemplo.lan";
?>
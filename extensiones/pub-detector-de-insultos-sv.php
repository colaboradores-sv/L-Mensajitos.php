<?php
/**
 * Detector de Insultos Salvadoreños PHP
 *
 * Detecta si una cadena posee alguno de los insultos utilizados en el español
 * hablado en El Salvador
 *
 * PHP Version 5
 *
 * Licencia MIT:
 *
 * Copyright (c) 2012
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 * @author     Colaboradores de Listasal.info <colaboradores@listasal.com.sv>
 * @copyright  2012
 * @license    http://#  MIT License
 * @version    0.3.0
 * @link       http://www.listasal.info/desarrolladores/disv
 *
 */

// ** Configuración ** //
/* ¿Desea recibir una notificación cuando se detecte un insulto? */
define( 'DISV_NOTIFICAR' , false );

/* Correo electrónico que origina la notificación */
define( 'DISV_CORREO_ORIGEN' , 'origen@ejemplo.lan' );

/* Correo electrónico para recibir la notificación */
define( 'DISV_CORREO_DESTINO', 'destino@ejemplo.lan' );

// ** Casos a Filtrar ** //
$disv_filtro = array( 
	/* Insultos */
	"/\b(cabron|cabrona|cabrones|kbron|kabron|kabrona|kbrona|cabrn)\b/",
	"/\b(cerote|cerota|cerotas|cerote)\b/",
	"/\b(culero|culeros|qlero|qleros|kulero|kuleros)\b/",
	"/\b(chimado|chimados|chimada|chimadas)\b/",
	"/\b(i(m|n)(b|v)e(c|s)il)\b/",
	"/\b(serote|serota|serotas|serote)\b/",
	"/\b(maldito|maldita|malditas|malditos)\b/",
	"/\b(malnacido|malnacidos|malnacida|malnacidas)\b/",
	"/\b(malparido|malparidos|malparida|malparidas)\b/",
	"/\b(mierd|mierda|mierdas)\b/",
	"/\b(pendejo|pendejos|pendej0|pendej0s|pendeja|pendejas|pendej)\b/",
	"/\b(puta|puto|putos|put0|put0s|putaa|putaaa|putha|putoo|putooo|putho)\b/",
	"/\b(zorra|zorras)\b/",

	/* Reproductivas */
	"/\b(verga|vergas|vrga|vergas|berga|bergas|brga)\b/",

	/* EXTRAS: */
	"/\b(mor(oso|osa))\b/"
);

/* Casos a filtrar, escritos como regular expressions */
$disv_filtro2 = array(
	//Más de 2 caracteres repetidos
	//Ejemplo: aaa, bbb, 555
	"/.*([a-z])\\1{2,}.*/",
	"/.*([0-9])\\1{2,}.*/",
	
	//Más de 2 caracteres difernetes a 0-9, A-Z y a-z repetidos
	// Ejemplo: ..., !!!
	"/.*([^0-9A-Za-z])\\1{2,}.*/",	
	
	//Palabras filtradas en las firmas
    "/\b(anon(i|y)m(o|os|a|as|ous|us))\b/",
    "/\b(a|@).*(n).*(o|0).*(n).*(i|1).*(m).*(o|0)\b/", // anonimo
        
	//Puede colocar aquí más palabras para filtrar en las firmas.
		
				
			
	

);

function detectar_insulto ( $_texto, $_filtro ) {
	$_insulto = '';
    
	foreach ( $_filtro as $_var => $_pattern ) {
		//echo "$_pattern ";		
		if ( preg_match ( $_pattern, strtolower ( $_texto ), $_matches) ) {
			$_insulto =  "$_matches[0]";			
		}
    }
    
	/**
	 *  Reportar incidencia detecta via correo electronico
	 */	
    
    if ( $_insulto != '' && DISV_NOTIFICAR == true) {
		$to      = DISV_CORREO_DESTINO;
		$headers = 'From: '     . DISV_CORREO_ORIGEN . "\r\n" .
				   'Reply-To: ' . DISV_CORREO_ORIGEN . "\r\n";
		$subject = "Insulto detectado <$_insulto> $_texto";
		$body    = $_texto . ' Cookie: '. obtenerCookieUID() . ' IP: ' . getenv("REMOTE_ADDR");  ;
		mail($to, $subject, $body, $headers);
	}
	
	return $_insulto;
}
?>
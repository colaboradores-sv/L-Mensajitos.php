<?php 
/******************************************************************************
 * Licencia original:                                                         *
 ******************************************************************************
 *
 * Mensajitos V2.3.1
 * Actualizado el 14/10/2006_**
 * Este programa es propiedad intelectual de
 * Mario Enrique Gomez Argueta. Su uso y distribucion
 * esta permitida bajo los terminos de la licencia
 * GNU/GPL 2.0 o posterior, que puede ser obtenida en
 * http://www.fsf.org/
 *
 * Este programa hace uso de la biblioteca Snoopy
 * distribuida tambien bajo la licencia GNU/GPL.
 * Para mayor informacion visitar:
 * http://snoopy.sourceforge.net/
 *
 ******************************************************************************
 * Anexo de Todosv                                                            *
 ******************************************************************************
 * Esta es la rama experimental NO oficial: "xMensajitos.php".
 * Esta rama experimental es  mantenida por Carlos Vladimir Hidalgo Durán.
 * Contacto: vladimiroski@gmail.com
 * Más Información : http://xmensajitos.todosv.com
 *
 ******************************************************************************
 * Anexo de Listasal                                                          *
 ******************************************************************************
 * Esta es una versión modificada por Listasal: "L-Mensajitos.php"
 * Contacto: colaboradores arroba listasal punto com punto sv
 * Más información: http://www.listasal.info/desarrolladores/l-mensajitos
 *****************************************************************************/

/**
 * EXTENSIONES 
 */
header( 'Content-Type: text/html; charset=UTF-8' );
require_once './extensiones/pub-detector-de-insultos-sv.php';
require_once './extensiones/pub-cookie.php';
mb_internal_encoding( 'UTF-8' );

/*
 * Explicaciones varias
 * visual = Obliga a usar una plantilla especifica si ese especificada
 */

/**
 * CONFIGURACIONES 
 */
require_once('./config.php');

$MiVersion = '3.5.0 R [BETA]';
// Definimos las constantes de directorios para poder accesarlas desde todo el codigo
$home      = dirname(__FILE__);


$modulos   = array(
		'Digicel',
		'Telecom',
		'Red',
		'Telefonica',
		'Tigo'
);

// En data.php se almacenan los datos de acceso al servidor MySQL.
// Este archivo es creado via /instalar.php
require_once($home . "/datos/data.php");

//**************************************************
// Si no hay soporte MySQL:

// Este archivo guarda los datos para el control de Flood
$nMDB             = $home . "/datos/numeros.db";
// Este archivo guarda las estadÃƒÂ­sticas
$cMDB             = $home . "/datos/cuentas.db";
// Este archivo guarda los nÃƒÂºmeros fuera de rango
$r_fuera_de_rango = $home . "/datos/fuera_rango.db";
// Cargamos la Clase necesaria para manipular INIs
require_once($home . "/libs/iniparser.php");
//**************************************************

// Cargamos la Clase para comunicarnos con POST/GET facilmente
require_once($home . "/libs/snoopy.php");
// Nuestras funciones para uso de proxys
require_once($home . "/libs/proxymity.php");
// Pre-cargamos todos los modulos
// Esto tiene que ser mejorado, se debe cargar solo el modulo necesario al enviar el mensaje
foreach ($modulos as $item => $elemento)
    require_once($home . "/modulos/" . $elemento . ".php");
/*************************************************************************/
// Tratamos de conectarnos a la base de datos, si lo conseguimos entonces
// activamos la variable que indicara que se pueden utilizar las funciones
// dependientes de MiDB.
// Este metodo deberia de asegurar que no se pierda funcionalidad principal
// al no tener configurado MiBD.

/*************************************************************************/
$MiBD_link = @mysql_connect($MiBD_IP, $MiBD_usuario, $MiBD_clave, false);
if (!$MiBD_link) {
    //No nos pudimos conectar
    $MiBD_OK = false;
} else {
    //Si nos pudimos conectar, entonces todo depende que podamos escoger sin problemas
    //la base de datos.
    $MiBD_OK = @mysql_select_db($MiBD_BD, $MiBD_link);
}

//Ok, si no tenemos MiBD entonces regresamos al viejo y confiable sistema INI.

if ( !$MiBD_OK ) {
    $I_nMDB = new iniParser($nMDB);
    $I_cMDB = new iniParser($cMDB);
} else {
    //echo 'conectado<br />';
}


/*************************************************************************/
// Mensajes

/*************************************************************************/
//Envio exitoso
$mensajeOK    = "<b>Mensaje enviado a <br /> +503 {uNumero}.<br />De la red de {operador}.</b>";
//Envio fallido
$mensajeERROR = "<b>Error al enviar el mensaje.<br />Se uso el operador: {operador}.</b>";
//No habia Operador válido
$mensajeOPEP  = "<b>Error al enviar el mensaje.<br />Revise el numero ({uNumero},{operador}).</b>";
//No pasa el filtro
$mensajeFILTRO = "<b>Error al enviar el mensaje.<br />La palabra '' no es permitida";
/*************************************************************************/

/**
 * FUNCIONES 
 */

function agregarNumFueraDeRango($Numero)
{
    global $MiBD_OK;
    if ($MiBD_OK) {
        global $MiBD_link;
        $q = "INSERT IGNORE INTO xsms_fuera_de_rango VALUES ('$Numero');";
        @mysql_query($q, $MiBD_link);
    } else {
        $I_FR_MDB = new iniParser($r_fuera_de_rango);
        $I_FR_MDB->setValue($Numero, "Hit", "SI");
        $I_FR_MDB->save();
    }
}

function EstablecerValorSQL($sTabla, $sValores)
{
    global $MiBD_OK, $MiBD_link;
    if ($MiBD_OK) {
        $q         = "REPLACE INTO $sTabla VALUES ($sValores);";
        //echo $q."<br>";
        $resultado = @mysql_query($q, $MiBD_link);
        if ($resultado) {
            return true;
        } else {
            return false;
        }
    }
}

function InsertarValorSQL($sTabla, $sValores, $OnUpdate)
{
    global $MiBD_OK, $MiBD_link;
    if ($MiBD_OK) {
        $q         = "INSERT INTO $sTabla VALUES ($sValores) ON DUPLICATE KEY UPDATE $OnUpdate;";
        //echo $q."<br>";
        $resultado = @mysql_query($q, $MiBD_link);
        if ($resultado) {
            return true;
        } else {
            return false;
        }
    }
}

// Detecta el modulo a utilizar en base al numero de telefono
require_once($home . "/rangos.php");

function ObtenerValorSQL($sTabla, $sColumna, $sWhere)
{
    global $MiBD_OK, $MiBD_link;
    if ($MiBD_OK) {
        $q         = "SELECT $sColumna FROM $sTabla WHERE $sWhere;";
        //echo $q."<br>";
        $resultado = @mysql_query($q, $MiBD_link);
        if (mysql_num_rows($resultado) > 0) {
            return mysql_result($resultado, 0, $sColumna);
        } else {
            return false;
        }
    }
}
function procesarPlantilla($archivo, $valores)
{
    $buffer = file_get_contents($archivo);
    foreach ($valores as $var => $val) {
        $buffer = str_replace($var, $val, $buffer);
    }
    return $buffer;
}
// </FUNCIONES>

if (isset($_POST['encuesta-respuesta'])) {
    guardarEncuesta();
}

if (stristr($_SERVER['HTTP_ACCEPT'], "text/vnd.wap.wml")) {
    // Es un dispositivo movil, soporta WML
    $plantilla = $home . "/plantilla/mensajitos.wml";
    $mime      = "text/vnd.wap.wml";
    header("Content-type: $mime");
} else {
    // No soporta wml (o no quiere xD)
    
    /*****************************************/
    //Sera que quiere un modo de presentacion especial
    if (isset($_GET['visual'])) {
        switch ($_GET['visual']) {
            case "iframe":
                $plantilla = $home . "/plantilla/mensajitos.iframe.htm";
                break;
        }
    } else {
        $plantilla = $home . "/plantilla/mensajitos.htm";
    }
    $mime = "text/html";
}

if ($MiBD_OK) {
    InsertarValorSQL("xsms_estadisticas", "'$mime','1'", "valor=valor+1");
}

//Sera que quieren hacer un GET?

if ($EnviaXGet){
    if(isset($_GET['t'])&&isset($_GET['m'])&&isset($_GET['f'])) {
        $_POST['telefono'] = $_GET['t'];
        $_POST['mensaje'] = $_GET['m'];
        $_POST['firma'] = $_GET['f'];
    } else if(isset($_GET['o'])) {
        $modulB = ($modulB = ModuloOperador($_GET['o'])) ? $modulB : '?';
        exit ($modulB);
    }
}

// Evaluamos el formulario basico
if (isset($_POST['telefono']) && isset($_POST['mensaje']) && isset($_POST['firma'])) {
	// Verificamos que no se haya establecido nada en vars
	if (isset($vars))
        unset($vars);
	// Guardamos las variables:
	$telefono = strip_tags( $_POST['telefono'] );
	$mensaje  = strip_tags( $_POST['mensaje'] );
	$firma    = strip_tags( $_POST['firma'] );
    
    //Sustituye caracteres especiales del $mensaje y $firma por similares
    $a       = 'ÑñŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ';
    $b       = 'NnSOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy';
    $mensaje = utf8_decode( $mensaje );
    $mensaje = strtr( $mensaje, utf8_decode( $a ), $b );
    $firma   = utf8_decode( $firma );
    $firma   = strtr( $firma, utf8_decode( $a ), $b );
    
    //************************************************
    // Revision de respuesta (Si se envia o no)
    //$url_ok = $_POST['urlok'];
    //$url_bad = $_POST['urlbad'];
    //************************************************
    
	// Procesamos el Mensaje y La Firma en búsqueda de insultos
	$insulto_detectado = '';
	$insulto_detectado = detectar_insulto ( "$firma | $mensaje", $disv_filtro );

	// TODO: Procesamos solo La Firma en búsqueda de firmas inválidas
	$problema_en_firma = '';
	$problema_en_firma = detectar_insulto( $firma, $disv_filtro2 );
	
    //Comprobamos que el mensaje lleve firma
    if ( $firma == '' || strlen($firma) <= 2 ){
        $estado = "<span class='error'>Error en tu firma</span><br />Escribe tu <strong>nombre y apellido</strong> verdadero para enviar el mensaje";
    //Comprobamos que la firma no sea inaceptable	
	} elseif ( $problema_en_firma != ''  ) {
		$estado = "<span class='error'>Error en tu firma</span><br />Escribe tu nombre y apellido <strong>verdadero</strong> para enviar el mensaje";

	//Comprobamos que no sea publicidad, cobro, etc.	
    } elseif (  $insulto_detectado != '' ) {        
        $estado = "<span class='error'>Error en tu mensaje :|</span><br /L-Mensajitos no acepta mensajes de cobro, crimen o con insultos. No puedes usar la palabra: <em>'$insulto_detectado'</em>";

        //exit('Lo sentimos, este tipo de mensaje no es aceptado.');
    } elseif ( $telefono == '12345678' //Ejemplo para bloquear el numero 1234-5678			   			   
			   ) {
        //Validamos el numero telefonico
        $estado = "<span class='error'>Lo siento :(</span><br />No puedo enviar mensajes a ese número.";
    } elseif ( $telefono == "" || !ereg( "^((2|6|7)[0-9]{7})$" , $telefono) ) {
        $estado = "Escribe el número correctamente";
    }
    //Verificamos que el numero de celular no este bloqueado
    else {
        if ($MiBD_OK) {
			//Identificamos a cada usuario con la dirección IP+Cookie			        
			$ipYCookie = $_SERVER['REMOTE_ADDR'] . '-' . substr( obtenerCookieUID(), 0 , 12 ) ;
        
            $cuentaNum = ObtenerValorSQL("xsms_flood", "valor", "clave='$telefono.cuenta'");
            $ultimoNum = ObtenerValorSQL("xsms_flood", "valor", "clave='$telefono.ultimo'");
            $cuentaIP  = ObtenerValorSQL("xsms_flood", "valor", "clave='" . $ipYCookie . ".cuenta'");
            $ultimoIP  = ObtenerValorSQL("xsms_flood", "valor", "clave='" . $ipYCookie . ".ultimo'");
        } else {
            //Comprobamos que no tenga ban.
            //Cuenta de mensajes a ese numero
            $cuentaNum = $I_nMDB->getValue( $telefono, "cuenta" );
            //Cuando se envio por ultima vez un mensaje a ese numero
            $ultimoNum = $I_nMDB->getValue( $telefono, "ultimo" );
            //Cuenta de mensajes desde esa IP+Cookie            
            $cuentaIP  = $I_nMDB->getValue( $ipYCookie, "cuenta" );
            //Cuando esa IP+Cookie nos envio por ultima vez un mensaje
            $ultimoIP  = $I_nMDB->getValue( $ipYCookie, "ultimo" );
        }
        //-------------------------------------------------
        $flooder = 0;
        if (((time() - $ultimoIP) < $intervalo_flood) && ($cuentaIP > $limite_flood_ip)) {
            //Si no ha pasado una hora desde su ultimo mensaje y ha enviado mas mensajes de la cuenta (IP)
            $estado = "Demasiados mensajes por hora desde tu maquina.";
            if ($MiBD_OK) {
                EstablecerValorSQL( "xsms_flood", "'" . $ipYCookie . ".ultimo', '" . time() . "'" );
                EstablecerValorSQL( "xsms_flood", "'" . $ipYCookie . ".flood', '1'" );
            } else {
                $I_nMDB->setValue( $ipYCookie, "ultimo", time() );
                $I_nMDB->setValue( $ipYCookie, "flood", 1 );
            }
            $flooder = 1;
        } else if (((time() - $ultimoNum) < $intervalo_flood) && ($cuentaNum > $limite_flood_num)) {
            //Si no ha pasado una hora desde su ultimo mensaje y ha enviado mas mensajes de la cuenta (Numero)
            $estado = "Demasiados mensajes por hora a este numero.";
            if ($MiBD_OK) {
                EstablecerValorSQL("xsms_flood", "'$telefono.flood', '1'");
            } else {
                $I_nMDB->setValue($telefono, "flood", 1);
            }
            $flooder = 1;
        }
        if ((time() - $ultimoIP) > $intervalo_flood) {
            //Si ha pasado una hora desde su ultimo mensaje (IP) le reseteamos su conteo (IP)
            $cuentaIP = 0;
            if ($MiBD_OK) {
                EstablecerValorSQL( "xsms_flood", "'" . $ipYCookie . ".flood', '0'" );
                EstablecerValorSQL( "xsms_flood", "'" . $ipYCookie . ".cuenta, '0'" );
            } else {
                $I_nMDB->setValue( $ipYCookie, "flood", 0);
                $I_nMDB->setValue( $ipYCookie, "cuenta", 0);
            }
        }
        if ((time() - $ultimoNum) > $intervalo_flood) {
            //Si ha pasado una hora desde su ultimo mensaje (Num) le reseteamos su conteo (Num)
            $cuentaNum = 0;
            if ($MiBD_OK) {
                EstablecerValorSQL("xsms_flood", "'$telefono.flood', '0'");
                EstablecerValorSQL("xsms_flood", "'$telefono.cuenta', '0'");
            } else {
                $I_nMDB->setValue($telefono, "flood", 0);
                $I_nMDB->setValue($telefono, "cuenta", 0);
            }
        }
        if ($flooder == 0) {
            //Ok, no tiene banneo por flood.
            //Ok, el numero es valido, pero ha escrito un mensaje a enviar?.
            if ($mensaje) {
                // Si, ha escrito un mensaje ahora buscar un operador para el numero.
                $modulo = ModuloOperador($telefono);
                if ($modulo) {
                    $nombreMod = $modulo . "_Nombre";
                    $ret       = $nombreMod();
                    $FEnvio    = $modulo . "_Enviar";

                    if ($FEnvio($telefono, $mensaje, $firma)) {
                        $estado = $mensajeOK;
                        //Control de Flood
                        if ($MiBD_OK) {
                            EstablecerValorSQL("xsms_flood", "'" . $ipYCookie . ".cuenta', '" . ($cuentaIP += 1) . "'");
                            EstablecerValorSQL("xsms_flood", "'" . $ipYCookie . ".ultimo', '" . time() . "'");
                            EstablecerValorSQL("xsms_flood", "'$telefono.cuenta', '" . ($cuentaNum += 1) . "'");
                            EstablecerValorSQL("xsms_flood", "'$telefono.ultimo', '" . time() . "'");
                        } else {
                            $I_nMDB->setValue($ipYCookie, "cuenta", $cuentaIP += 1);
                            $I_nMDB->setValue($ipYCookie, "ultimo", time());
                            $I_nMDB->setValue($telefono, "cuenta", $cuentaNum += 1);
                            $I_nMDB->setValue($telefono, "ultimo", time());
                        }
                        //Control de Flood
                        $mensaje = '';
                        //+1 al modulo OK
                        if ($MiBD_OK) {
                            InsertarValorSQL("xsms_estadisticas", "'" . $modulo . "-OK" . "','1'", "valor=valor+1");
                        } else {
                            $cuenta = $I_cMDB->getValue("Companias", $modulo . "-OK");
                            $I_cMDB->setValue("Companias", $modulo . "-OK", $cuenta += 1);
                        }
                    } else {
                        $estado = $mensajeERROR;
                        //+1 al modulo ERROR
                        if ($MiBD_OK) {
                            InsertarValorSQL("xsms_estadisticas", "'" . $modulo . "-ERR" . "','1'", "valor=valor+1");
                        } else {
                            $cuenta = $I_cMDB->getValue("Companias", $modulo . "-ERR");
                            $I_cMDB->setValue("Companias", $modulo . "-ERR", $cuenta += 1);
                        }
                    }
                } else {
                    $estado = $mensajeOPEP;
                }
            } else {
                $estado = "Olvido escribir su mensaje";
            }
        }
        if ($MiBD_OK) {
        } else {
            $I_cMDB->save();
            $I_nMDB->save();
        }
    }
}

//Cuando se envia con un numero preseleccionado
if ( isset( $_GET['preseleccion'] ) && is_numeric( $_GET['preseleccion'] ) )
	$telefono = substr(strip_tags($_GET['preseleccion']), 0, 8);

$_cuentaIP = ObtenerValorSQL("xsms_flood", "valor", "clave='" . $_SERVER['REMOTE_ADDR'] . ".cuenta'");

//Informacion del formulario
$vars["{version}"]  = '<a href="#" target="_blank">Version ' . $MiVersion . '</a>';
$vars["{estado}"]   = $estado;
$vars["{clase}"]    = $vars["{operador}"] = $ret;
$vars["{uNumero}"]  = $telefono;
$vars["{uMensaje}"] = utf8_encode($mensaje);
$vars["{uFirma}"]   = utf8_encode($firma);
$vars["{cuenta}"]   = $_cuentaIP; 

// Sustituimos los valores en la plantilla:
echo procesarPlantilla( $plantilla, $vars );
return 0;
?>
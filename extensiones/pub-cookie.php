<?php
function asignarCookieUID() {
        if (!isset($_COOKIE['l-mensajitos-uid'])) {
            $name = 'l-mensajitos-uid';
            $id = uniqid();
            $expireDate = time() + 31536000   ;
            $path = '/';
            $domain = LM_DOMINIO;
            $secure = false; //only transmit the cookie if a HTTPS connection is established
            $httponly = true; //make cookie available only for the HTTP protocol (and not for JavaScript)    	
            setcookie( $name, $value, $expireDate, $path, $domain, $secure, $httponly);
        }
}
function obtenerCookieUID() {
    $cookieUID = $_COOKIE['l-mensajitos-uid'];
    return $cookieUID;
}
?>
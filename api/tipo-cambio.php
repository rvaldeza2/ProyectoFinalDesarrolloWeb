<?php

header('Content-Type: application/json; charset=utf-8');

// Servicio del Banco de Guatemala
const BANGUAT_URL = 'https://www.banguat.gob.gt/variables/ws/TipoCambio.asmx';
const BANGUAT_NS  = 'http://www.banguat.gob.gt/variables/ws/';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responder(405, ['ok' => false, 'mensaje' => 'Método no permitido. Use GET.']);
}

$hoy = tipo_cambio_hoy();

if (!$hoy['ok']) {
    responder(502, ['ok' => false, 'mensaje' => 'No se pudo consultar al Banguat: ' . $hoy['error']]);
}

$respuesta = [
    'ok'         => true,
    'fuente'     => 'Banco de Guatemala',
    'fecha'      => $hoy['fecha'],
    'referencia' => $hoy['referencia'],
];

// Si mandan ?dolares= se convierte a quetzales
if (isset($_GET['dolares'])) {
    $dolares = (float) $_GET['dolares'];
    if ($dolares < 0) {
        responder(400, ['ok' => false, 'mensaje' => 'La cantidad de dólares no puede ser negativa.']);
    }
    $respuesta['dolares']   = $dolares;
    $respuesta['quetzales'] = round($dolares * $hoy['referencia'], 2);
}

responder(200, $respuesta);

// Le hace la consulta al Banguat
function banguat_llamar($funcion, $parametros = '')
{
    $xml_pregunta =
        '<?xml version="1.0" encoding="utf-8"?>'
        . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
        . '<soap:Body>'
        . '<' . $funcion . ' xmlns="' . BANGUAT_NS . '">'
        . $parametros
        . '</' . $funcion . '>'
        . '</soap:Body></soap:Envelope>';

    $ch = curl_init(BANGUAT_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_pregunta);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: text/xml; charset=utf-8',
        'SOAPAction: "' . BANGUAT_NS . $funcion . '"',
    ]);

    $xml_respuesta = curl_exec($ch);
    $codigo        = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($xml_respuesta === false) {
        return ['ok' => false, 'error' => 'sin conexión (' . curl_error($ch) . ')'];
    }
    if ($codigo !== 200) {
        return ['ok' => false, 'error' => 'respondió con el código ' . $codigo];
    }
    return ['ok' => true, 'xml' => $xml_respuesta];
}

// Saca un dato de la respuesta del Banguat
function valor_xml($xml, $etiqueta)
{
    $patron = '/<' . $etiqueta . '>(.*?)<\/' . $etiqueta . '>/s';

    if (preg_match($patron, $xml, $encontrado)) {
        return trim($encontrado[1]);
    }
    return '';
}

function tipo_cambio_hoy()
{
    $r = banguat_llamar('TipoCambioDia');
    if (!$r['ok']) {
        return $r;
    }

    // El dato del dólar viene dentro de <VarDolar>
    $bloque = valor_xml($r['xml'], 'VarDolar');
    if ($bloque === '') {
        return ['ok' => false, 'error' => 'la respuesta no trae el bloque VarDolar'];
    }

    return [
        'ok'         => true,
        'fecha'      => valor_xml($bloque, 'fecha'),
        'referencia' => (float) valor_xml($bloque, 'referencia'),
    ];
}

// Envía la respuesta al cliente
function responder($codigo, $datos)
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

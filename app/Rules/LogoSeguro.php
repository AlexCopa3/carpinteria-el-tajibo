<?php

namespace App\Rules;

use Closure;
use DOMDocument;
use DOMProcessingInstruction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class LogoSeguro implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return;
        }
        if (strtolower($value->getClientOriginalExtension()) !== 'svg' && $value->getMimeType() !== 'image/svg+xml') {
            return;
        }
        $contenido = file_get_contents($value->getRealPath());
        $documento = new DOMDocument();
        $erroresPrevios = libxml_use_internal_errors(true);
        try {
            if (preg_match('/<!DOCTYPE|<!ENTITY/i', $contenido) || ! $documento->loadXML($contenido, LIBXML_NONET)
                || ! $documento->documentElement || $documento->documentElement->localName !== 'svg') {
                $fail('El logo SVG no es válido.');
                return;
            }
            $permitidos = ['svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'text', 'tspan', 'defs', 'linearGradient', 'radialGradient', 'stop', 'clipPath', 'mask', 'title', 'desc', 'use', 'symbol'];
            foreach ($documento->getElementsByTagName('*') as $elemento) {
                if (! in_array($elemento->localName, $permitidos, true) || $elemento->namespaceURI !== 'http://www.w3.org/2000/svg') {
                    $fail('El logo SVG debe contener únicamente gráficos, sin código ni contenido externo.');
                    return;
                }
                foreach ($elemento->attributes as $atributo) {
                    $nombre = strtolower($atributo->localName);
                    $valor = trim($atributo->value);
                    if ($atributo->namespaceURI === 'http://www.w3.org/2000/xmlns/') {
                        continue;
                    }
                    if (str_starts_with($nombre, 'on') || $nombre === 'style'
                        || ($nombre === 'href' && ! preg_match('/^#[a-zA-Z_][a-zA-Z0-9_.:-]*$/', $valor))
                        || preg_match('/javascript:|data:|https?:|\/\/|@import|expression\s*\(/i', $valor)
                        || (stripos($valor, 'url(') !== false && ! preg_match('/^url\(\s*#[a-zA-Z_][a-zA-Z0-9_.:-]*\s*\)$/i', $valor))) {
                        $fail('El logo SVG no puede incluir scripts, estilos ni referencias externas.');
                        return;
                    }
                }
            }
            foreach ($documento->childNodes as $nodo) {
                if ($nodo instanceof DOMProcessingInstruction) {
                    $fail('El logo SVG no puede incluir instrucciones externas.');
                    return;
                }
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($erroresPrevios);
        }
    }
}

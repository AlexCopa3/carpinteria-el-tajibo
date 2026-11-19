<?php

namespace Tests\Feature;

use Tests\TestCase;

class IntegridadPerfilSimilitudTest extends TestCase
{
    public function test_perfil_publicado_coincide_con_huella_configurada(): void
    {
        $perfil = config('similitud.perfil');
        $archivo = config_path($perfil['archivo']);
        $contenido = file_get_contents($archivo);

        $this->assertStringNotContainsString("\r", $contenido);
        $this->assertSame($perfil['sha256'], hash('sha256', $contenido));
        $this->assertSame($perfil['sha256'], hash('sha256', str_replace("\r\n", "\n", $contenido)));
    }
}

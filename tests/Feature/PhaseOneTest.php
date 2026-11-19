<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PhaseOneTest extends TestCase
{
    public function test_login_publico_renderiza_adminlte_en_espanol_sin_consultas(): void
    {
        DB::listen(function (): void {
            $this->fail('La pantalla de acceso no debe consultar la base de datos.');
        });
        $response = $this->get('/login');
        $response->assertOk()->assertSee('lang="es"', false)->assertSee('Iniciar sesión')
            ->assertSee('vendor/adminlte/dist/css/adminlte.min.css', false)
            ->assertSee('vendor/bootstrap/js/bootstrap.bundle.min.js', false)
            ->assertDontSee('tailwind', false)->assertDontSee('cdn.', false);
        $this->assertSame(1, substr_count($response->getContent(), 'vendor/bootstrap/js/bootstrap.bundle.min.js'));
    }

    public function test_registro_publico_deshabilitado(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_configuracion_regional_y_pruebas_aisladas(): void
    {
        $this->assertSame('es', config('app.locale'));
        $this->assertSame('America/La_Paz', config('app.timezone'));
        $this->assertSame('BOB', config('tajibo.moneda.codigo'));
        $this->assertSame('Bs', config('tajibo.moneda.simbolo'));
        $this->assertTrue(config('similitud.habilitado'));
        $this->assertNotNull(config('similitud.perfil.archivo'));
        $this->assertSame(3, config('similitud.k'));
        $this->assertSame('80', config('similitud.umbral'));
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }

    public function test_assets_publicados_son_bootstrap_cuatro(): void
    {
        $this->assertMatchesRegularExpression('/Bootstrap v4\.6\./', file_get_contents(public_path('vendor/bootstrap/js/bootstrap.bundle.min.js')));
        $this->assertFileExists(public_path('vendor/jquery/jquery.min.js'));
        $this->assertFileExists(public_path('build/manifest.json'));
    }
}

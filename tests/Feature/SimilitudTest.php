<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\CasoHistorico;
use App\Models\Categoria;
use App\Models\Material;
use App\Models\MaterialCaso;
use App\Models\TipoMueble;
use App\Models\UnidadMaterial;
use App\Models\User;
use App\Services\CatalogoService;
use App\Services\CotizacionService;
use App\Services\PropuestaCasoService;
use App\Services\RecuperacionCasosService;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\TiposMuebleSeeder;
use Database\Seeders\UnidadesSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Support\CasosPerfil;
use Tests\TestCase;

class SimilitudTest extends TestCase
{
    use RefreshDatabase;

    private User $propietario;

    private User $maestro;

    private array $archivos = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->seed([PermisosSeeder::class, UnidadesSeeder::class, TiposMuebleSeeder::class]);
        $this->propietario = User::factory()->create()->assignRole('propietario');
        $this->maestro = User::factory()->create()->assignRole('maestro');
        $documento = json_decode(file_get_contents(database_path('seeders/data/casos_historicos.json')), true);
        $creados = [];
        foreach ($documento['casos'] as $caso) {
            foreach ($caso['materiales'] as $material) {
                if (isset($creados[$material['material']])) {
                    continue;
                }$creados[$material['material']] = true;
                $categoria = Categoria::firstOrCreate(['nombre' => in_array($material['material'], ['Melamina', 'Madera'], true) ? 'Material principal' : 'Accesorios']);
                app(CatalogoService::class)->guardar($this->propietario, ['nombre' => $material['material'], 'categoria_id' => $categoria->id, 'unidad_id' => UnidadMaterial::where('codigo', $material['unidad'])->value('id'), 'precio_referencia' => $material['costo_unitario_estimado_bs'], 'activo' => true]);
            }
        }
        config(['datos_sistema.habilitado' => true]);
        CasosPerfil::cargar();
        // Los escenarios históricos de esta suite conservan su perfil al 80 %.
        $perfil = config_path('perfiles/similitud-casos-historicos-v2.json');
        config(['similitud.umbral' => '80', 'similitud.pesos' => json_decode(file_get_contents($perfil), true)['parametros']['pesos'], 'similitud.perfil' => ['archivo' => $perfil, 'sha256' => hash_file('sha256', $perfil)]]);

        $this->actingAs($this->maestro);
    }

    protected function tearDown(): void
    {
        foreach ($this->archivos as $archivo) {
            if (is_file($archivo)) {
                unlink($archivo);
            }
        }
        parent::tearDown();
    }

    private function cotizar(array $cambios = [])
    {
        $material = Material::where('nombre', 'Madera')->firstOrFail();
        $detalle = ['tipo_mueble_id' => TipoMueble::where('codigo', 'mesa')->value('id'), 'material_principal_id' => $material->id, 'largo_cm' => '150', 'ancho_cm' => '85', 'alto_cm' => '76', 'puertas' => 0, 'cajones' => 0, 'divisiones' => 0, 'acabado' => 'Barnizado natural', 'cantidad_muebles' => 2, 'mano_obra_unitaria' => '20', 'otros_costos_total' => '5', 'materiales' => [['material_id' => $material->id, 'componente' => 'General', 'cantidad_unitaria' => '2', 'costo_unitario_estimado' => '75', 'estado_costo' => 'conocido', 'fuente_costo' => 'estimacion_manual']], ...$cambios];
        $cotizacion = app(CotizacionService::class)->guardar($this->propietario, ['fecha' => today()->toDateString(), 'clave_registro' => (string) Str::uuid(), 'detalles' => [$detalle]]);

        return [$cotizacion, $cotizacion->detalles->first()];
    }

    private function buscar(array $par = []): array
    {
        [$cotizacion,$detalle] = $par ?: $this->cotizar();

        return app(RecuperacionCasosService::class)->buscar($this->maestro, $cotizacion, $detalle);
    }

    private function ruta(array $par): string
    {
        return route('admin.similitud.show', [$par[0], $par[1]]);
    }

    private function instantanea(): array
    {
        $resultado = [];
        foreach (DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'") as $t) {
            $resultado[$t->name] = DB::table($t->name)->get()->toJson();
        }

        return $resultado;
    }

    private function perfil(array $perfil): void
    {
        $dir = storage_path('framework/testing');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $archivo = $dir.'/perfil-'.Str::uuid().'.json';
        file_put_contents($archivo, json_encode($perfil, JSON_THROW_ON_ERROR));
        $this->archivos[] = $archivo;
        config(['similitud.perfil' => ['archivo' => $archivo, 'sha256' => hash_file('sha256', $archivo)]]);
    }

    public function test_ejemplos_aprobados_ordenados_sin_cambiar_ninguna_tabla(): void
    {
        $par = $this->cotizar();
        $antes = $this->instantanea();
        $r = $this->buscar($par);
        $this->assertSame('encontrados', $r['estado']);
        $this->assertSame(['96.63', '88.63'], array_column($r['resultados'], 'porcentaje'));
        $this->assertSame(array_map(fn ($posicion) => CasoHistorico::where('posicion_lote', $posicion)->sole()->id, [9, 12]), array_column($r['resultados'], 'id'));
        $this->get($this->ruta($par))->assertOk()->assertDontSee('Demostración')->assertDontSee('Consultar casos reales')->assertSee('96,63')->assertSee('88,63')->assertDontSee('recargo_porcentaje')->assertDontSee('Aplicar referencia');
        $this->assertSame($antes, $this->instantanea());
    }

    public function test_otro_tipo_misma_madera_no_se_mezcla_y_un_solo_vecino_es_valido(): void
    {
        $par = $this->cotizar(['tipo_mueble_id' => TipoMueble::where('codigo', 'armario')->value('id'), 'largo_cm' => '150', 'ancho_cm' => '55', 'alto_cm' => '200', 'puertas' => 3, 'cajones' => 2, 'divisiones' => 5, 'cantidad_muebles' => 1]);
        $r = $this->buscar($par);
        $this->assertCount(1, $r['resultados']);
        $this->assertSame('86.00', $r['resultados'][0]['porcentaje']);
        $this->assertSame('Armario', $r['resultados'][0]['tipo']);
    }

    public function test_consulta_unica_recupera_casos_sin_reclasificar_su_procedencia(): void
    {
        $par = $this->cotizar();
        $antes = $this->instantanea();
        $resultado = $this->buscar($par);
        $this->assertSame(array_map(fn ($posicion) => CasoHistorico::where('posicion_lote', $posicion)->sole()->id, [9, 12]), array_column($resultado['resultados'], 'id'));
        $this->assertSame(['simulado', 'simulado'], array_column($resultado['resultados'], 'origen'));
        $this->assertArrayNotHasKey('modo', $resultado);
        $this->assertSame($antes, $this->instantanea());
    }

    public function test_cobertura_estricta_no_extrapola_y_un_rango_constante_no_divide_por_cero(): void
    {
        $r = $this->buscar($this->cotizar(['largo_cm' => '300']));
        $this->assertSame('sin_cobertura', $r['estado']);
        $this->assertSame([], $r['resultados']);
        $this->assertContains('Largo (cm)', $r['fuera_cobertura']);
        $r = $this->buscar($this->cotizar(['puertas' => 1]));
        $this->assertSame('sin_cobertura', $r['estado']);
        $this->assertContains('Puertas', $r['fuera_cobertura']);
    }

    public function test_caso_de_referencia_descartado_o_descriptor_cambiado_invalida_perfil(): void
    {
        DB::table('casos_historicos')->where('posicion_lote', 1)->update(['estado_revision' => 'descartado']);
        $this->assertSame('perfil_invalido', $this->buscar()['estado']);
        DB::table('casos_historicos')->where('posicion_lote', 1)->update(['estado_revision' => 'pendiente', 'largo_cm' => '101']);
        $this->assertSame('perfil_invalido', $this->buscar()['estado']);
    }

    public function test_revisar_sin_cambiar_descriptores_mantiene_la_huella(): void
    {
        $par = $this->cotizar();
        $antes = $this->buscar($par)['perfil'];
        DB::table('casos_historicos')->where('posicion_lote', 1)->update(['estado_revision' => 'revisado', 'materiales_revisados_at' => now(), 'precio_revisado_at' => now(), 'materiales_revisados_por_id' => $this->propietario->id, 'precio_revisado_por_id' => $this->propietario->id, 'version' => 3]);
        $this->assertSame($antes, $this->buscar($par)['perfil']);
    }

    public function test_configuracion_modificada_archivo_alterado_y_motor_apagado_son_explicitos(): void
    {
        $par = $this->cotizar();
        config(['similitud.umbral' => '75']);
        $this->assertSame('perfil_invalido', $this->buscar($par)['estado']);
        config(['similitud.umbral' => '80', 'similitud.perfil.sha256' => str_repeat('0', 64)]);
        $this->assertSame('perfil_invalido', $this->buscar($par)['estado']);
        config(['similitud.habilitado' => false]);
        $this->assertSame('desactivado', $this->buscar($par)['estado']);
    }

    public function test_perfil_incompleto_unidad_incompatible_o_costo_desconocido_no_compara(): void
    {
        $par = $this->cotizar();
        $perfil = json_decode(file_get_contents(config_path('perfiles/similitud-casos-historicos-v1.json')), true);
        unset($perfil['referencias'][0]['acabado']);
        $this->perfil($perfil);
        $this->assertSame('perfil_invalido', $this->buscar($par)['estado']);
    }

    public function test_permisos_separados_en_endpoint_y_servicio_con_usuario_inactivo(): void
    {
        $par = $this->cotizar();
        $rol = $this->maestro->roles->first();
        foreach (['cotizaciones.consultar', 'casos.consultar', 'costos.consultar'] as $p) {
            $rol->revokePermissionTo($p);
            $this->get($this->ruta($par))->assertForbidden();
            $rol->givePermissionTo($p);
        }
        $this->maestro->activo = false;
        $this->maestro->save();
        $this->expectException(AuthorizationException::class);
        $this->buscar($par);
    }

    public function test_detalle_ajeno_y_campos_de_perfil_o_precio_se_rechazan(): void
    {
        $par = $this->cotizar();
        $otro = $this->cotizar();
        $this->get($this->ruta([$par[0], $otro[1]]))->assertNotFound();
        $this->getJson($this->ruta($par).'?umbral=0')->assertUnprocessable()->assertJsonValidationErrors('solicitud');
        $this->getJson($this->ruta($par).'?precio=1')->assertUnprocessable();
        $this->getJson($this->ruta($par).'?modo=mezclado')->assertUnprocessable()->assertJsonValidationErrors('solicitud');
    }

    public function test_consulta_unica_no_depende_de_un_modo_por_entorno(): void
    {
        $par = $this->cotizar();
        app()->detectEnvironment(fn () => 'production');
        try {
            $this->get($this->ruta($par))->assertOk()->assertDontSee('Demostración');
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }
        config(['similitud.perfil' => null]);
        $this->assertSame('sin_perfil', $this->buscar($par)['estado']);
    }

    public function test_maximo_tres_y_desempate_por_id_sin_usar_precios(): void
    {
        $par = $this->cotizar();
        $ids = [];
        for ($i = 0; $i < 4; $i++) {
            $ids[] = CasoHistorico::factory()->create(['tipo_mueble_id' => $par[1]->tipo_mueble_id, 'material_principal_id' => $par[1]->material_principal_id, 'material_principal_nombre' => 'Madera', 'largo_cm' => '150', 'ancho_cm' => '85', 'alto_cm' => '76', 'cantidad_muebles' => 2])->id;
        }
        $r = $this->buscar($par);
        $this->assertSame(array_slice($ids, 0, 3), array_column($r['resultados'], 'id'));
        $this->assertSame(['100.00', '100.00', '100.00'], array_column($r['resultados'], 'porcentaje'));
        DB::table('casos_historicos')->where('id', $ids[0])->update(['precio_historico_total' => '99999']);
        $this->assertSame(array_slice($ids, 0, 3), array_column($this->buscar($par)['resultados'], 'id'));
        DB::table('casos_historicos')->where('id', $ids[0])->update(['estado_revision' => 'descartado']);
        $this->assertSame(array_slice($ids, 1, 3), array_column($this->buscar($par)['resultados'], 'id'));
    }

    public function test_casos_desde_pedido_y_registros_iniciales_comparten_perfil_sin_cambiar_origen(): void
    {
        $par = $this->cotizar();
        $caso = CasoHistorico::factory()->create([
            'origen' => 'real', 'estado_revision' => 'revisado',
            'tipo_mueble_id' => $par[1]->tipo_mueble_id, 'material_principal_id' => $par[1]->material_principal_id,
            'material_principal_nombre' => 'Madera', 'largo_cm' => '150', 'ancho_cm' => '85', 'alto_cm' => '76',
            'cantidad_muebles' => 2,
        ]);
        $antes = $this->instantanea();
        $resultado = $this->buscar($par);
        $this->assertSame($caso->id, $resultado['resultados'][0]['id']);
        $this->assertSame(['real', 'simulado', 'simulado'], array_column($resultado['resultados'], 'origen'));
        $this->assertSame($antes, $this->instantanea());
    }

    public function test_costo_o_unidad_de_referencia_incompletos_invalidan_perfil(): void
    {
        $par = $this->cotizar();
        $caso = CasoHistorico::where('posicion_lote', 1)->first();
        $material = $caso->materiales->first();
        DB::table('materiales_caso')->where('id', $material->id)->update(['costo_unitario_estimado' => null]);
        $this->assertSame('perfil_invalido', $this->buscar($par)['estado']);
        DB::table('materiales_caso')->where('id', $material->id)->update(['costo_unitario_estimado' => $material->costo_unitario_estimado, 'unidad_id' => UnidadMaterial::where('codigo', 'metro')->value('id')]);
        $this->assertSame('perfil_invalido', $this->buscar($par)['estado']);
    }

    public function test_pesos_vigentes_reducen_penalizacion_de_medidas_y_conservan_umbral_70(): void
    {
        $perfil = config_path('perfiles/similitud-casos-historicos-v4.json');
        $parametros = json_decode(file_get_contents($perfil), true)['parametros'];
        config(['similitud.umbral' => $parametros['umbral'], 'similitud.pesos' => $parametros['pesos'], 'similitud.perfil' => ['archivo' => $perfil, 'sha256' => hash_file('sha256', $perfil)]]);
        $this->assertSame(100, array_sum(config('similitud.pesos')));
        $par = $this->cotizar();
        $r = $this->buscar($par);
        $principal = CasoHistorico::where('posicion_lote', 9)->sole();
        $this->assertSame('encontrados', $r['estado']);
        $this->assertSame('70', $r['perfil']['umbral']);
        $this->assertSame($principal->id, $r['resultados'][0]['id']);
        // Largo: 10/100*10=1; ancho: 5/40*10=1.25; resto coincide.
        $this->assertSame('97.75', $r['resultados'][0]['porcentaje']);
        $this->get($this->ruta($par))->assertOk()->assertSee('Dimensiones 30 %')->assertSee('material principal 30 %')->assertSee('puertas 7 %')->assertSee('divisiones 6 %');
        $melamina = Material::where('nombre', 'Melamina')->sole();
        $limite = CasoHistorico::factory()->create(['tipo_mueble_id' => $par[1]->tipo_mueble_id, 'material_principal_id' => $melamina->id, 'material_principal_nombre' => $melamina->nombre, 'largo_cm' => '150', 'ancho_cm' => '85', 'alto_cm' => '76', 'cantidad_muebles' => 2, 'acabado' => 'Barnizado natural']);
        $r = $this->buscar($par);
        $this->assertCount(3, $r['resultados']);
        $this->assertSame('70.00', collect($r['resultados'])->firstWhere('id', $limite->id)['porcentaje']);
    }

    public function test_perfil_vigente_admite_70_y_75_pero_excluye_inferiores_sin_exceder_tres(): void
    {
        $perfil = config_path('perfiles/similitud-casos-historicos-v3.json');
        config(['similitud.umbral' => '70', 'similitud.perfil' => ['archivo' => $perfil, 'sha256' => hash_file('sha256', $perfil)]]);
        $par = $this->cotizar();
        $atributos = ['tipo_mueble_id' => $par[1]->tipo_mueble_id, 'material_principal_id' => $par[1]->material_principal_id, 'material_principal_nombre' => 'Madera', 'largo_cm' => '100', 'ancho_cm' => '65', 'alto_cm' => '76', 'cantidad_muebles' => 1, 'acabado' => 'Pintado'];
        $debajo = CasoHistorico::factory()->create([...$atributos, 'largo_cm' => '99.9999']);
        $this->assertNotContains($debajo->id, array_column($this->buscar($par)['resultados'], 'id'));
        $exacto = CasoHistorico::factory()->create($atributos);
        $r = $this->buscar($par);
        $this->assertSame('encontrados', $r['estado']);
        $this->assertSame('70', $r['perfil']['umbral']);
        $this->assertContains($exacto->id, array_column($r['resultados'], 'id'));
        $this->assertSame('70.00', collect($r['resultados'])->firstWhere('id', $exacto->id)['porcentaje']);
        $this->assertNotContains($debajo->id, array_column($r['resultados'], 'id'));
        $intermedio = CasoHistorico::factory()->create([...$atributos, 'largo_cm' => '133.3334']);
        $r = $this->buscar($par);
        $this->assertContains($intermedio->id, array_column($r['resultados'], 'id'));
        $this->assertCount(3, $r['resultados']);
        $this->assertNotContains($debajo->id, array_column($r['resultados'], 'id'));
    }

    public function test_filtro_del_umbral_no_acepta_799999_aunque_se_muestre_8000(): void
    {
        $par = $this->cotizar();
        $atributos = ['tipo_mueble_id' => $par[1]->tipo_mueble_id, 'material_principal_id' => $par[1]->material_principal_id, 'material_principal_nombre' => 'Madera', 'largo_cm' => '116.6660', 'ancho_cm' => '85', 'alto_cm' => '76', 'cantidad_muebles' => 1, 'acabado' => 'Pintado'];
        $debajo = CasoHistorico::factory()->create($atributos);
        $r = $this->buscar($par);
        $this->assertCount(2, $r['resultados']);
        $this->assertNotContains($debajo->id, array_column($r['resultados'], 'id'));
        $encima = CasoHistorico::factory()->create([...$atributos, 'largo_cm' => '116.6667']);
        $r = $this->buscar($par);
        $this->assertCount(3, $r['resultados']);
        $this->assertSame($encima->id, $r['resultados'][2]['id']);
        $this->assertSame('80.00', $r['resultados'][2]['porcentaje']);
    }

    public function test_agregar_un_atipico_no_amplia_la_cobertura_o_la_escala(): void
    {
        $par = $this->cotizar(['largo_cm' => '300']);
        $antes = $this->buscar($par);
        CasoHistorico::factory()->create(['tipo_mueble_id' => $par[1]->tipo_mueble_id, 'material_principal_id' => $par[1]->material_principal_id, 'material_principal_nombre' => 'Madera', 'largo_cm' => '1000', 'ancho_cm' => '85', 'alto_cm' => '76', 'cantidad_muebles' => 2]);
        $despues = $this->buscar($par);
        $this->assertSame('sin_cobertura', $despues['estado']);
        $this->assertSame($antes['perfil'], $despues['perfil']);
        $this->assertSame([], $despues['resultados']);
    }

    public function test_candidato_adicional_incompleto_no_se_rellena_con_cero_o_catalogo(): void
    {
        $par = $this->cotizar();
        $caso = CasoHistorico::factory()->create(['tipo_mueble_id' => $par[1]->tipo_mueble_id, 'material_principal_id' => $par[1]->material_principal_id, 'material_principal_nombre' => 'Madera', 'largo_cm' => '150', 'ancho_cm' => '85', 'alto_cm' => '76', 'cantidad_muebles' => 2]);
        DB::table('materiales_caso')->where('caso_historico_id', $caso->id)->update(['costo_unitario_estimado' => null, 'subtotal_estimado' => null]);
        $r = $this->buscar($par);
        $this->assertSame(array_map(fn ($posicion) => CasoHistorico::where('posicion_lote', $posicion)->sole()->id, [9, 12]), array_column($r['resultados'], 'id'));
        $this->assertNull(DB::table('materiales_caso')->where('caso_historico_id', $caso->id)->value('costo_unitario_estimado'));
    }

    private function descripcionFormulario(array $cambios = []): array
    {
        return [
            'tipo_mueble_id' => TipoMueble::where('codigo', 'mesa')->value('id'),
            'material_principal_id' => Material::where('nombre', 'Madera')->value('id'),
            'largo_cm' => '150', 'ancho_cm' => '85', 'alto_cm' => '76',
            'cantidad_muebles' => 2, 'puertas' => 0, 'cajones' => 0, 'divisiones' => 0,
            'acabado' => 'Barnizado natural', ...$cambios,
        ];
    }

    public function test_busqueda_desde_formulario_no_exige_guardar_materiales_costos_o_precios(): void
    {
        $antes = $this->instantanea();
        $respuesta = $this->postJson(route('admin.similitud.buscar'), [
            'mueble' => $this->descripcionFormulario(),
        ])->assertOk()->assertJsonPath('cantidad', 2)->assertJsonPath('estado', 'encontrados');
        $this->assertStringContainsString('Recomendación principal', $respuesta->json('html'));
        $this->assertStringContainsString('Usar propuesta principal', $respuesta->json('html'));
        $propuestas = $respuesta->json('propuestas');
        $this->assertTrue($propuestas[0]['principal']);
        $this->assertFalse($propuestas[1]['principal']);
        $this->assertTrue($propuestas[0]['aplicable']);
        $this->assertSame('250.00', $propuestas[0]['mano_obra_unitaria']);
        $this->assertSame('30.00', $propuestas[0]['otros_costos_total']);
        $this->assertArrayNotHasKey('precio_aprobado', $propuestas[0]);
        $this->assertSame($antes, $this->instantanea());
    }

    public function test_formulario_busca_directamente_y_no_inventa_tres_vecinos(): void
    {
        $this->postJson(route('admin.similitud.buscar'), ['mueble' => $this->descripcionFormulario()])
            ->assertOk()->assertJsonPath('cantidad', 2);
        $this->postJson(route('admin.similitud.buscar'), ['mueble' => $this->descripcionFormulario(['largo_cm' => '300'])])
            ->assertOk()->assertJsonPath('estado', 'sin_cobertura')->assertJsonPath('cantidad', 0);
    }

    public function test_formulario_rechaza_descripcion_incompleta_negativos_parametros_y_precios(): void
    {
        foreach ([
            ['mueble' => ['tipo_mueble_id' => 1]],
            ['mueble' => $this->descripcionFormulario(['largo_cm' => '-1'])],
            ['mueble' => $this->descripcionFormulario(['cantidad_muebles' => '1.5'])],
            ['mueble' => $this->descripcionFormulario(['acabado' => 'No autorizado'])],
            ['mueble' => $this->descripcionFormulario(['precio_final' => 1])],
            ['mueble' => $this->descripcionFormulario(), 'umbral' => 0],
        ] as $datos) {
            $this->postJson(route('admin.similitud.buscar'), $datos)->assertUnprocessable();
        }
        $accesorio = Material::where('nombre', 'Tornillo')->value('id');
        $this->postJson(route('admin.similitud.buscar'), ['mueble' => $this->descripcionFormulario(['material_principal_id' => $accesorio])])->assertUnprocessable();
    }

    public function test_propuesta_usa_precio_actual_y_no_convierte_desconocido_en_cero(): void
    {
        $madera = Material::where('nombre', 'Madera')->firstOrFail();
        $madera->update(['precio_referencia' => '100']);
        $resultado = $this->postJson(route('admin.similitud.buscar'), ['mueble' => $this->descripcionFormulario()])->assertOk();
        $material = collect($resultado->json('propuestas.0.materiales'))->firstWhere('material_id', $madera->id);
        $this->assertSame('8', $material['cantidad_unitaria']);
        $this->assertSame('100.000000', $material['costo_unitario_estimado']);
        $madera->update(['precio_referencia' => '0']);
        $resultado = $this->postJson(route('admin.similitud.buscar'), ['mueble' => $this->descripcionFormulario()])->assertOk();
        $material = collect($resultado->json('propuestas.0.materiales'))->firstWhere('material_id', $madera->id);
        $this->assertNull($material['costo_unitario_estimado']);
        $this->assertSame('pendiente', $material['estado_costo']);
        $this->assertNull($resultado->json('propuestas.0.costo_actual_total'));
    }

    public function test_formulario_exige_crear_o_modificar_y_bloquea_cotizacion_aprobada(): void
    {
        [$cotizacion] = $this->cotizar();
        $datos = ['mueble' => $this->descripcionFormulario()];
        $this->maestro->revokePermissionTo('cotizaciones.crear');
        // Retirar el permiso heredado solo en este fixture.
        $this->maestro->roles->first()->revokePermissionTo('cotizaciones.crear');
        $this->postJson(route('admin.similitud.buscar'), $datos)->assertForbidden();
        $this->postJson(route('admin.similitud.buscarEditar', $cotizacion), $datos)->assertOk();
        $cotizacion->update(['aprobado_at' => now()]);
        $this->postJson(route('admin.similitud.buscarEditar', $cotizacion), $datos)->assertForbidden();
    }

    public function test_formulario_consulta_con_csrf_en_produccion_sin_selector_de_modo(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->withSession(['_token' => 'csrf-valido-para-prueba'])->postJson(route('admin.similitud.buscar'), [
            '_token' => 'csrf-valido-para-prueba', 'mueble' => $this->descripcionFormulario(),
        ])->assertOk()->assertJsonPath('cantidad', 2);
    }

    public function test_propuesta_se_guarda_editable_sin_modificar_antecedentes_y_con_procedencia(): void
    {
        $casosAntes = CasoHistorico::with('materiales')->get()->toJson();
        $mueble = $this->descripcionFormulario();
        $respuesta = $this->postJson(route('admin.similitud.buscar'), ['mueble' => $mueble])->assertOk();
        $propuesta = $respuesta->json('propuestas.0');
        $materiales = array_map(fn ($material) => Arr::only($material, [
            'material_id', 'componente', 'cantidad_unitaria', 'costo_unitario_estimado', 'estado_costo', 'fuente_costo',
        ]), $propuesta['materiales']);
        $mueble = [...$mueble, 'mano_obra_unitaria' => '275.00', 'otros_costos_total' => $propuesta['otros_costos_total'],
            'materiales' => $materiales, 'referencia_caso' => $propuesta['referencia_caso']];
        $cotizacion = app(CotizacionService::class)->guardar($this->maestro, [
            'fecha' => today()->toDateString(), 'clave_registro' => (string) Str::uuid(), 'detalles' => [$mueble],
        ]);
        $this->assertSame('275.00', $cotizacion->detalles->first()->mano_obra_unitaria);
        $this->assertNull($cotizacion->aprobado_at);
        $this->assertSame($casosAntes, CasoHistorico::with('materiales')->get()->toJson());
        $auditoria = Auditoria::where('accion', 'referencia_caso_utilizada')->sole();
        $this->assertSame($propuesta['caso_id'], $auditoria->datos_nuevos['referencia']['caso_id']);
        $this->assertSame('simulado', $auditoria->datos_nuevos['referencia']['origen']);
    }

    public function test_referencia_alterada_o_de_otro_actor_no_se_guarda(): void
    {
        $resultado = $this->postJson(route('admin.similitud.buscar'), ['mueble' => $this->descripcionFormulario()])->assertOk();
        $token = $resultado->json('propuestas.0.referencia_caso');
        $antes = $this->instantanea();
        try {
            app(PropuestaCasoService::class)->verificarReferencia($this->propietario, $token);
            $this->fail('Se aceptó una referencia de otro actor.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('referencia_caso', $error->errors());
        }
        $propuesta = $resultado->json('propuestas.0');
        $datos = [...$this->descripcionFormulario(),
            'mano_obra_unitaria' => $propuesta['mano_obra_unitaria'],
            'otros_costos_total' => $propuesta['otros_costos_total'],
            'referencia_caso' => 'alterado',
            'materiales' => array_map(fn ($material) => Arr::only($material, [
                'material_id', 'componente', 'cantidad_unitaria', 'costo_unitario_estimado', 'estado_costo', 'fuente_costo',
            ]), $propuesta['materiales']),
        ];
        // El registro completo también revierte si se altera la procedencia.
        try {
            app(CotizacionService::class)->guardar($this->maestro, [
                'fecha' => today()->toDateString(), 'clave_registro' => (string) Str::uuid(), 'detalles' => [$datos],
            ]);
            $this->fail('Se aceptó una referencia alterada.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('referencia_caso', $error->errors());
        }
        $this->assertSame($antes, $this->instantanea());
    }

    public function test_selects_y_contadores_enviados_como_texto_se_comparan_correctamente(): void
    {
        $mueble = array_map(fn ($valor) => (string) $valor, $this->descripcionFormulario());
        $this->postJson(route('admin.similitud.buscar'), ['mueble' => $mueble])
            ->assertOk()->assertJsonPath('cantidad', 2)->assertJsonPath('propuestas.0.principal', true);
    }

    public function test_propuesta_respeta_unidades_y_no_multiplica_otros_costos_fijos(): void
    {
        $madera = Material::where('nombre', 'Madera')->firstOrFail();
        $madera->update(['precio_referencia' => '10']);
        $madera->unidad->update(['fraccionaria' => false]);
        $caso = CasoHistorico::factory()->make([
            'material_principal_id' => $madera->id, 'cantidad_muebles' => 2,
            'mano_obra_estimada_total' => '101', 'otros_costos_estimados_total' => '7',
        ]);
        $caso->save();
        $linea = new MaterialCaso;
        $linea->forceFill([
            'caso_historico_id' => $caso->id, 'posicion' => 1, 'material_id' => $madera->id,
            'unidad_id' => $madera->unidad_id, 'material_nombre' => $madera->nombre,
            'unidad_nombre' => $madera->unidad->nombre, 'componente' => 'General',
            'cantidad_total' => '5', 'costo_unitario_estimado' => '10',
            'subtotal_estimado' => '50', 'fuente_costo' => 'cotizacion_capturada',
        ])->save();
        $resultado = ['perfil' => ['version' => 'prueba'], 'resultados' => [['id' => $caso->id, 'porcentaje' => '90.00']]];
        $propuesta = app(PropuestaCasoService::class)->preparar($this->maestro, $this->descripcionFormulario(['cantidad_muebles' => 3]), $resultado)[0];
        $this->assertSame('3', $propuesta['materiales'][0]['cantidad_unitaria']);
        $this->assertTrue($propuesta['redondeadas']);
        $this->assertSame('50.50', $propuesta['mano_obra_unitaria']);
        $this->assertSame('7.00', $propuesta['otros_costos_total']);
        $this->assertSame('248.50', $propuesta['costo_actual_total']);
        $this->assertSame('248.50', $propuesta['costo_historico_adaptado_total']);
        $this->assertTrue($propuesta['lote_diferente']);
    }

    public function test_comparacion_economica_usa_misma_cantidad_y_costo_historico_ponderado(): void
    {
        $madera = Material::where('nombre', 'Madera')->firstOrFail();
        $madera->update(['precio_referencia' => '10']);
        $madera->unidad->update(['fraccionaria' => false]);
        $caso = CasoHistorico::factory()->make([
            'material_principal_id' => $madera->id, 'cantidad_muebles' => 2,
            'mano_obra_estimada_total' => '101', 'otros_costos_estimados_total' => '7',
            'costo_materiales_estimado_total' => '80', 'costo_estimado_total' => '188',
        ]);
        $caso->save();
        foreach ([[2, 10, 20], [3, 20, 60]] as $indice => [$cantidad, $costo, $subtotal]) {
            $linea = new MaterialCaso;
            $linea->forceFill([
                'caso_historico_id' => $caso->id, 'posicion' => $indice + 1,
                'material_id' => $madera->id, 'unidad_id' => $madera->unidad_id,
                'material_nombre' => $madera->nombre, 'unidad_nombre' => $madera->unidad->nombre,
                'componente' => 'Componente '.($indice + 1), 'cantidad_total' => $cantidad,
                'costo_unitario_estimado' => $costo, 'subtotal_estimado' => $subtotal,
                'fuente_costo' => 'cotizacion_capturada',
            ])->save();
        }
        $antes = $this->instantanea();
        $resultado = ['perfil' => ['version' => 'prueba'], 'resultados' => [['id' => $caso->id, 'porcentaje' => '90.00']]];
        $propuesta = app(PropuestaCasoService::class)->preparar($this->maestro, $this->descripcionFormulario(['cantidad_muebles' => 3]), $resultado)[0];
        // 5 piezas / 2 muebles -> 3 enteras por mueble; 9 piezas para 3.
        // Costo histórico ponderado: (2*10 + 3*20)/5 = 16; no promedio simple.
        $this->assertSame('302.50', $propuesta['costo_historico_adaptado_total']);
        $this->assertSame('248.50', $propuesta['costo_actual_total']);
        $this->assertSame('188.00', $propuesta['costo_historico_grupo']);
        $this->assertTrue($propuesta['lote_diferente']);
        $this->assertSame($antes, $this->instantanea());
    }

    public function test_comparacion_distingue_catalogo_pendiente_y_no_avisa_lote_si_no_cambia(): void
    {
        Material::where('nombre', 'Madera')->update(['precio_referencia' => '0']);
        $respuesta = $this->postJson(route('admin.similitud.buscar'), ['mueble' => $this->descripcionFormulario()])->assertOk();
        $propuesta = $respuesta->json('propuestas.0');
        $this->assertNull($propuesta['costo_actual_total']);
        $this->assertNotNull($propuesta['costo_historico_adaptado_total']);
        $this->assertFalse($propuesta['lote_diferente']);
        $this->assertStringContainsString('Con costos del caso', $respuesta->json('html'));
        $this->assertStringContainsString('Con precios actuales', $respuesta->json('html'));
        $procedencia = app(PropuestaCasoService::class)->verificarReferencia($this->maestro, $propuesta['referencia_caso']);
        $this->assertSame($propuesta['costo_historico_adaptado_total'], $procedencia['referencia_economica']['historico_adaptado_total']);
        $this->assertNull($procedencia['referencia_economica']['actual_total']);
    }

    public function test_comparacion_no_inventa_costo_historico_si_falta_una_linea(): void
    {
        $resultado = app(RecuperacionCasosService::class)->buscarFormulario($this->maestro, $this->descripcionFormulario());
        $caso = CasoHistorico::findOrFail($resultado['resultados'][0]['id']);
        DB::table('materiales_caso')->where('id', $caso->materiales->first()->id)->update(['costo_unitario_estimado' => null, 'subtotal_estimado' => null]);
        $propuesta = app(PropuestaCasoService::class)->preparar($this->maestro, $this->descripcionFormulario(), $resultado)[0];
        $this->assertNull($propuesta['costo_historico_adaptado_total']);
        $this->assertNotNull($propuesta['costo_actual_total']);
    }

    public function test_veladores_comodas_y_vitrinas_recuperan_su_tipo_y_respetan_cobertura(): void
    {
        foreach (['velador', 'comoda', 'vitrina'] as $codigo) {
            $tipo = TipoMueble::where('codigo', $codigo)->sole();
            $caso = CasoHistorico::where('tipo_mueble_id', $tipo->id)->orderBy('id')->firstOrFail();
            $datos = $caso->only(['tipo_mueble_id', 'material_principal_id', 'largo_cm', 'ancho_cm', 'alto_cm', 'puertas', 'cajones', 'divisiones', 'cantidad_muebles', 'acabado']);
            $resultado = app(RecuperacionCasosService::class)->buscarFormulario($this->propietario, $datos);
            $this->assertSame('encontrados', $resultado['estado']);
            $this->assertGreaterThan(0, count($resultado['resultados']));
            $this->assertLessThanOrEqual(3, count($resultado['resultados']));
            $this->assertSame('100.00', $resultado['resultados'][0]['porcentaje']);
            foreach ($resultado['resultados'] as $fila) {
                $this->assertSame($tipo->nombre, $fila['tipo']);
            }
            $datos['largo_cm'] = '9999';
            $this->assertSame('sin_cobertura', app(RecuperacionCasosService::class)->buscarFormulario($this->propietario, $datos)['estado']);
        }
    }
}

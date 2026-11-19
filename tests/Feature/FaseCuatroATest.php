<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\DetalleCotizacion;
use App\Models\Material;
use App\Models\MaterialCotizacion;
use App\Models\MovimientoMaterial;
use App\Models\PermisoUsuario;
use App\Models\TipoMueble;
use App\Models\UnidadMaterial;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Services\CatalogoService;
use App\Services\CotizacionService;
use App\Services\PermisosUsuarioService;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\TiposMuebleSeeder;
use Database\Seeders\UnidadesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FaseCuatroATest extends TestCase
{
    use RefreshDatabase;

    private User $propietario;

    private User $maestro;

    private Cliente $cliente;

    private Material $material;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->seed([PermisosSeeder::class, UnidadesSeeder::class, TiposMuebleSeeder::class]);
        $this->propietario = User::factory()->create()->assignRole('propietario');
        $this->maestro = User::factory()->create()->assignRole('maestro');
        $this->cliente = Cliente::create(['nombre' => 'Cliente aislado', 'tipo' => 'persona', 'telefono' => '70000000', 'activo' => true]);
        $categoria = Categoria::create(['nombre' => 'Material principal']);
        $this->material = app(CatalogoService::class)->guardar($this->propietario, [
            'nombre' => 'Material de prueba', 'categoria_id' => $categoria->id,
            'unidad_id' => UnidadMaterial::where('codigo', 'metro')->value('id'),
            'precio_referencia' => '40', 'activo' => true,
        ]);
    }

    private function datos(): array
    {
        return [
            'clave_registro' => Str::uuid()->toString(), 'fecha' => '2026-11-03',
            'detalles' => [[
                'tipo_mueble_id' => TipoMueble::where('codigo', 'mesa')->value('id'),
                'material_principal_id' => $this->material->id,
                'largo_cm' => '120', 'ancho_cm' => '60', 'alto_cm' => '75',
                'puertas' => 0, 'cajones' => 2, 'divisiones' => 0,
                'acabado' => 'Barnizado natural', 'cantidad_muebles' => 3,
                'mano_obra_unitaria' => '20', 'otros_costos_total' => '15',
                'materiales' => [[
                    'material_id' => $this->material->id, 'componente' => 'Estructura',
                    'cantidad_unitaria' => '2', 'costo_unitario_estimado' => '40',
                    'estado_costo' => 'conocido', 'fuente_costo' => 'referencia_catalogo',
                ]],
            ]],
        ];
    }

    private function crear(?array $datos = null): Cotizacion
    {
        $this->actingAs($this->propietario);
        $this->postJson('/admin/cotizaciones/create', $datos ?? $this->datos())->assertRedirect();

        return Cotizacion::latest('id')->firstOrFail()->load('detalles.materiales');
    }

    public function test_fecha_automatica_y_reenvio_en_otro_dia_no_duplican_la_cotizacion(): void
    {
        $this->travelTo(Carbon::parse('2026-11-18T02:30:00+00:00')->setTimezone('America/La_Paz'));
        $datos = $this->datos();
        unset($datos['fecha']);
        $cotizacion = $this->crear($datos);
        $this->assertSame('2026-11-17', $cotizacion->fecha->toDateString());
        $this->assertSame('2026-11-17 22:30:00', DB::table('cotizaciones')->where('id', $cotizacion->id)->value('created_at'));
        $this->get('/admin/cotizaciones/'.$cotizacion->id)->assertOk()->assertSee('17/11/2026 22:30');
        $this->get('/admin/cotizaciones/create')->assertOk()->assertDontSee('name="fecha"', false);

        $this->travelTo(now()->addDay());
        $datos['fecha'] = '1999-01-01';
        $this->postJson('/admin/cotizaciones/create', $datos)->assertRedirect();
        $this->assertSame(1, Cotizacion::count());
        $this->assertSame('2026-11-17', $cotizacion->fresh()->fecha->toDateString());
        $datos['clave_registro'] = Str::uuid()->toString();
        $nueva = $this->crear($datos);
        $this->assertSame('2026-11-18', $nueva->fecha->toDateString());
    }

    public function test_edicion_conserva_fecha_anterior_y_muestra_actualizacion_separada(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 17)->setTime(10, 0));
        $datos = $this->datos();
        $cotizacion = $this->crear($datos);
        // Una fecha histórica ya guardada se conserva también tras la actualización.
        DB::table('cotizaciones')->where('id', $cotizacion->id)->update(['fecha' => '2026-10-20']);
        $this->travelTo(now()->addDay()->setTime(9, 30));
        unset($datos['clave_registro']);
        $datos['version'] = $cotizacion->version;
        $datos['fecha'] = '2099-01-01';
        $datos['observaciones'] = 'Color acordado con el cliente.';
        $detalle = $cotizacion->detalles->sole();
        $datos['detalles'][0]['id'] = $detalle->id;
        $datos['detalles'][0]['materiales'][0]['id'] = $detalle->materiales->sole()->id;
        $this->get('/admin/cotizaciones/'.$cotizacion->id.'/edit')->assertOk()->assertDontSee('name="fecha"', false);
        $this->putJson('/admin/cotizaciones/'.$cotizacion->id, $datos)->assertRedirect();
        $actual = $cotizacion->fresh();
        $this->assertSame('2026-10-20', $actual->fecha->toDateString());
        $this->assertSame('2026-11-18 09:30', $actual->updated_at->format('Y-m-d H:i'));
        $this->get('/admin/cotizaciones/'.$cotizacion->id)->assertOk()
            ->assertSee('Fecha de cotización')->assertSee('20/10/2026')
            ->assertSee('Última actualización')->assertSee('18/11/2026 09:30');
    }

    public function test_selector_principal_filtra_categoria_y_estado_sin_limitar_materiales_de_costos(): void
    {
        $categoria = Categoria::create(['nombre' => 'Accesorios']);
        $secundario = Material::create(['nombre' => 'Tornillos', 'categoria' => 'Accesorios', 'categoria_id' => $categoria->id, 'unidad_id' => $this->material->unidad_id, 'precio_referencia' => '10', 'activo' => true]);
        $inactivo = Material::create(['nombre' => 'Principal inactivo', 'categoria' => 'Material principal', 'categoria_id' => $this->material->categoria_id, 'unidad_id' => $this->material->unidad_id, 'precio_referencia' => '20', 'activo' => false]);
        $this->actingAs($this->propietario)->get('/admin/cotizaciones/create')->assertOk()
            ->assertViewHas('principales', fn ($principales) => $principales->modelKeys() === [$this->material->id])
            ->assertViewHas('materiales', fn ($materiales) => $materiales->contains($secundario) && ! $materiales->contains($inactivo));
        $this->material->categoriaMaterial->update(['activo' => false]);
        $this->get('/admin/cotizaciones/create')->assertOk()->assertViewHas('principales', fn ($principales) => $principales->isEmpty())
            ->assertSee('No hay materiales principales disponibles.');
        $datos = $this->datos();
        $this->postJson('/admin/cotizaciones/create', $datos)->assertUnprocessable()->assertJsonValidationErrors('detalles.0.material_principal_id');
        $this->assertSame(0, Cotizacion::count());
    }

    public function test_servidor_rechaza_material_principal_de_otra_categoria(): void
    {
        $this->material->update(['categoria_id' => Categoria::create(['nombre' => 'Accesorios'])->id]);
        $datos = $this->datos();
        $this->actingAs($this->maestro)->postJson('/admin/cotizaciones/create', $datos)->assertUnprocessable()->assertJsonValidationErrors('detalles.0.material_principal_id');
        try {
            app(CotizacionService::class)->guardar($this->propietario, $datos);
            $this->fail('El servicio debe rechazar un principal fuera de su categoría.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('detalles.0.material_principal_id', $error->errors());
        }
        $this->assertSame(0, Cotizacion::count());
        $this->assertSame(0, DetalleCotizacion::count());
    }

    public function test_conserva_principal_anterior_solo_en_su_detalle_original(): void
    {
        $cotizacion = $this->crear();
        $this->material->update(['categoria_id' => Categoria::create(['nombre' => 'Anterior'])->id]);
        $this->get('/admin/cotizaciones/'.$cotizacion->id.'/edit')->assertOk()->assertSee('(registro anterior)');
        $edicion = $this->datos();
        unset($edicion['clave_registro']);
        $edicion['version'] = $cotizacion->version;
        $edicion['detalles'][0]['id'] = $cotizacion->detalles->sole()->id;
        $edicion['detalles'][0]['materiales'][0]['id'] = $cotizacion->detalles->sole()->materiales->sole()->id;
        $this->putJson('/admin/cotizaciones/'.$cotizacion->id, $edicion)->assertRedirect();
        $this->assertSame('315.00', $cotizacion->fresh()->costo_total);
        $this->assertSame($this->material->id, $cotizacion->fresh()->detalles->sole()->material_principal_id);
        $this->postJson('/admin/cotizaciones/create', $this->datos())->assertUnprocessable()->assertJsonValidationErrors('detalles.0.material_principal_id');
        $edicion['version'] = $cotizacion->fresh()->version;
        unset($edicion['detalles'][0]['id'], $edicion['detalles'][0]['materiales'][0]['id']);
        $this->putJson('/admin/cotizaciones/'.$cotizacion->id, $edicion)->assertUnprocessable()->assertJsonValidationErrors('detalles.0.material_principal_id');
        $this->assertSame(1, DetalleCotizacion::count());
    }

    public function test_acepta_las_cinco_opciones_de_acabado_y_rechaza_texto_nuevo(): void
    {
        foreach (['Barnizado natural', 'Pintado', 'Melamina', 'Sin acabado', 'Otros'] as $acabado) {
            $datos = $this->datos();
            $datos['detalles'][0]['acabado'] = $acabado;
            $cotizacion = $this->crear($datos);
            $this->assertSame($acabado, $cotizacion->detalles->sole()->acabado);
            $this->assertSame('315.00', $cotizacion->costo_total);
        }
        $datos = $this->datos();
        $datos['detalles'][0]['acabado'] = 'Laqueado';
        $this->postJson('/admin/cotizaciones/create', $datos)->assertUnprocessable()->assertJsonValidationErrors('detalles.0.acabado');
        $this->assertSame(5, Cotizacion::count());
    }

    public function test_conserva_acabado_anterior_solo_en_su_detalle_y_permite_cambiarlo(): void
    {
        $cotizacion = $this->crear();
        $detalle = $cotizacion->detalles->sole();
        DB::table('detalles_cotizacion')->where('id', $detalle->id)->update(['acabado' => 'Barniz nogal anterior']);
        $this->get('/admin/cotizaciones/'.$cotizacion->id.'/edit')->assertOk()->assertSee('Barniz nogal anterior (registro anterior)');
        $edicion = $this->datos();
        unset($edicion['clave_registro']);
        $edicion['version'] = $cotizacion->version;
        $edicion['detalles'][0]['id'] = $detalle->id;
        $edicion['detalles'][0]['materiales'][0]['id'] = $detalle->materiales->sole()->id;
        $edicion['detalles'][0]['acabado'] = 'Barniz nogal anterior';
        $this->putJson('/admin/cotizaciones/'.$cotizacion->id, $edicion)->assertRedirect();
        $this->assertSame('Barniz nogal anterior', $detalle->fresh()->acabado);
        $otra = $this->crear();
        $ajena = $edicion;
        $ajena['version'] = $otra->version;
        $ajena['detalles'][0]['id'] = $otra->detalles->sole()->id;
        $ajena['detalles'][0]['materiales'][0]['id'] = $otra->detalles->sole()->materiales->sole()->id;
        $this->putJson('/admin/cotizaciones/'.$otra->id, $ajena)->assertUnprocessable()->assertJsonValidationErrors('detalles.0.acabado');
        $edicion['version'] = $cotizacion->fresh()->version;
        $edicion['detalles'][0]['acabado'] = 'Pintado';
        $this->putJson('/admin/cotizaciones/'.$cotizacion->id, $edicion)->assertRedirect();
        $this->assertSame('Pintado', $detalle->fresh()->acabado);
    }

    public function test_servicio_rechaza_acabado_no_permitido_sin_depender_del_formulario(): void
    {
        $datos = $this->datos();
        $datos['detalles'][0]['acabado'] = 'Acabado no autorizado';
        try {
            app(CotizacionService::class)->guardar($this->propietario, $datos);
            $this->fail('Debió rechazarse el acabado.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('detalles.0.acabado', $error->errors());
        }
        $this->assertSame(0, Cotizacion::count());
    }

    private function precios(Cotizacion $cotizacion, string $precio = '400'): array
    {
        return ['version' => $cotizacion->version, 'precios' => $cotizacion->detalles->mapWithKeys(fn ($detalle) => [$detalle->id => $precio])->all()];
    }

    public function test_cotiza_aprueba_y_genera_pdf_sin_cliente(): void
    {
        $this->cliente->delete();
        $this->actingAs($this->propietario)->get('/admin/cotizaciones/create')->assertOk()->assertDontSee('id="cliente_id"', false)->assertDontSee('value="gratuito"', false);
        $cotizacion = $this->crear();
        $this->assertNull($cotizacion->cliente_id);
        $this->get('/admin/cotizaciones/'.$cotizacion->id.'/edit')->assertOk()->assertDontSee('id="cliente_id"', false);
        $this->post('/admin/cotizaciones/'.$cotizacion->id.'/aprobar', $this->precios($cotizacion))->assertRedirect();
        $this->assertNull($cotizacion->fresh()->datos_cliente_aprobados);
        $this->get('/admin/cotizaciones/'.$cotizacion->id)->assertOk();
        $this->get('/admin/cotizaciones')->assertOk();
        $pdf = $this->get('/admin/cotizaciones/'.$cotizacion->id.'/pdf')->assertOk();
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
    }

    public function test_no_acepta_datos_de_cliente_en_cotizacion(): void
    {
        $datos = $this->datos();
        $datos['cliente_id'] = $this->cliente->id;
        $this->actingAs($this->propietario)->postJson('/admin/cotizaciones/create', $datos)->assertUnprocessable()->assertJsonValidationErrors('cliente_id');
        $this->assertSame(0, Cotizacion::count());
    }

    public function test_cotizaciones_y_porcentaje_sin_valor_predeterminado(): void
    {
        $this->assertSame(27, DB::table('migrations')->count());
        $this->assertSame(6, TipoMueble::count());
        $this->assertFalse(Schema::hasTable('configuraciones_cotizacion'));
        $cotizacion = $this->crear();
        $this->assertNull($cotizacion->recargo_porcentaje);
        $this->assertNull($cotizacion->precio_sugerido);
        $this->assertSame('315.00', $cotizacion->costo_total);
        $this->assertSame(0, MovimientoMaterial::count());
        $this->assertNull($this->material->fresh()->existencia);
    }

    public function test_calcula_por_unidad_otros_costos_fijos_y_recargo_sobre_costo(): void
    {
        $cotizacion = $this->crear(array_replace($this->datos(), ['recargo_porcentaje' => '30']));
        $detalle = $cotizacion->detalles->sole();
        $this->assertSame('6.0000', $detalle->materiales->sole()->cantidad_total_nominal);
        $this->assertSame('240.00', $detalle->costo_materiales_total);
        $this->assertSame('60.00', $detalle->mano_obra_total_aplicada);
        $this->assertSame('15.00', $detalle->otros_costos_total);
        $this->assertSame('315.00', $detalle->costo_total);
        $this->assertSame('409.50', $cotizacion->precio_sugerido);
        $this->assertSame('120.0000', $detalle->largo_cm);
    }

    public function test_ajustes_conjuntos_preservan_nominales_y_exigen_motivo(): void
    {
        $datos = $this->datos();
        $datos['recargo_porcentaje'] = '30';
        $datos['detalles'][0]['otros_costos_total'] = '0';
        $datos['detalles'][0]['mano_obra_total_aplicada'] = '55';
        $datos['detalles'][0]['materiales'][0]['cantidad_total_aplicada'] = '5.5';
        $this->actingAs($this->propietario)->postJson('/admin/cotizaciones/create', $datos)->assertUnprocessable();
        $this->assertSame(0, Cotizacion::count());
        $datos['detalles'][0]['motivo_ajuste_mano_obra'] = 'Preparación en conjunto';
        $datos['detalles'][0]['materiales'][0]['motivo_ajuste_cantidad'] = 'Ahorro por fabricación conjunta';
        $cotizacion = $this->crear($datos);
        $detalle = $cotizacion->detalles->sole();
        $this->assertSame('20.00', $detalle->mano_obra_unitaria);
        $this->assertSame('60.00', $detalle->mano_obra_total_nominal);
        $this->assertSame('55.00', $detalle->mano_obra_total_aplicada);
        $this->assertSame('6.0000', $detalle->materiales->sole()->cantidad_total_nominal);
        $this->assertSame('5.5000', $detalle->materiales->sole()->cantidad_total_aplicada);
        $this->assertSame('275.00', $cotizacion->costo_total);
        $this->assertSame('357.50', $cotizacion->precio_sugerido);
    }

    public function test_formulario_sin_notas_generales_conserva_anteriores_y_observaciones_del_mueble(): void
    {
        $datos = $this->datos();
        $datos['observaciones'] = 'Coordinación general anterior.';
        $datos['condiciones'] = 'Entrega coordinada previamente con el cliente.';
        $cotizacion = $this->crear($datos);
        $detalle = $cotizacion->detalles->sole();
        $this->get('/admin/cotizaciones/create')->assertOk()->assertDontSee('name="condiciones"', false)->assertDontSee('name="observaciones"', false);
        $this->get('/admin/cotizaciones/'.$cotizacion->id.'/edit')->assertOk()
            ->assertDontSee('name="condiciones"', false)->assertDontSee('Notas y condiciones');
        unset($datos['clave_registro'], $datos['condiciones'], $datos['observaciones']);
        $datos['version'] = $cotizacion->version;
        $datos['detalles'][0]['observaciones'] = 'Color nogal para este mueble.';
        $datos['detalles'][0]['id'] = $detalle->id;
        $datos['detalles'][0]['materiales'][0]['id'] = $detalle->materiales->sole()->id;
        $this->putJson('/admin/cotizaciones/'.$cotizacion->id, $datos)->assertRedirect();
        $this->assertSame('Entrega coordinada previamente con el cliente.', $cotizacion->fresh()->condiciones);
        $this->assertSame('Coordinación general anterior.', $cotizacion->fresh()->observaciones);
        $this->assertSame('Color nogal para este mueble.', $detalle->fresh()->observaciones);
        $this->get('/admin/cotizaciones/'.$cotizacion->id)->assertOk()
            ->assertSee('Entrega coordinada previamente con el cliente.')->assertSee('Coordinación general anterior.')->assertSee('Color nogal para este mueble.');
        $nueva = $this->crear();
        $this->assertNull($nueva->condiciones);
        $this->assertNull($nueva->observaciones);
    }

    public function test_maestro_modifica_estimaciones_sin_administrar_ni_aprobar(): void
    {
        $this->actingAs($this->maestro);
        $datos = $this->datos();
        $this->postJson('/admin/cotizaciones/create', $datos)->assertRedirect();
        $cotizacion = Cotizacion::sole()->load('detalles.materiales');
        $edicion = $datos;
        unset($edicion['clave_registro']);
        $edicion['version'] = $cotizacion->version;
        $edicion['detalles'][0]['mano_obra_unitaria'] = '25';
        $this->putJson('/admin/cotizaciones/'.$cotizacion->id, $edicion)->assertRedirect();
        $this->assertSame('330.00', $cotizacion->fresh()->costo_total);
        $this->get('/admin/cotizaciones/'.$cotizacion->id)->assertOk()->assertDontSee('Ganancia sobre el costo')->assertDontSee('Revisar y aprobar precios');
        $this->get('/admin/cotizaciones/'.$cotizacion->id.'/edit')->assertOk()->assertDontSee('name="recargo_porcentaje"', false);
        $this->get('/admin/cotizaciones/configuracion')->assertNotFound();
        $this->maestro->givePermissionTo(['cotizaciones.aprobar', 'cotizaciones.validar', 'margenes.gestionar']);
        PermisoUsuario::create(['user_id' => $this->maestro->id, 'permission_id' => Permission::findByName('cotizaciones.aprobar')->id, 'permitido' => false]);
        $this->maestro->unsetRelation('permisosIndividuales');
        $this->postJson('/admin/cotizaciones/'.$cotizacion->id.'/aprobar', $this->precios($cotizacion->fresh()->load('detalles')))->assertForbidden();
        $this->post('/admin/cotizaciones/configuracion/create', ['porcentaje_recargo_predeterminado' => '50'])->assertNotFound();
    }

    public function test_no_acepta_totales_aprobadores_o_recargo_del_maestro(): void
    {
        $this->actingAs($this->maestro);
        foreach (['precio_final', 'costo_total', 'responsable_id', 'aprobado_por_id', 'estado', 'recargo_porcentaje'] as $campo) {
            $datos = $this->datos();
            $datos[$campo] = '30';
            $this->postJson('/admin/cotizaciones/create', $datos)->assertUnprocessable();
        }
        $datos = $this->datos();
        $datos['detalles'][0]['materiales'][0]['subtotal_aplicado'] = '0';
        $this->postJson('/admin/cotizaciones/create', $datos)->assertUnprocessable();
        $this->assertSame(0, Cotizacion::count());
    }

    public function test_desconocido_no_es_cero_y_bloquea_aprobacion(): void
    {
        $datos = $this->datos();
        $datos['detalles'][0]['materiales'][0]['estado_costo'] = 'pendiente';
        $datos['detalles'][0]['materiales'][0]['costo_unitario_estimado'] = null;
        $cotizacion = $this->crear($datos);
        $this->assertNull($cotizacion->costo_total);
        $this->assertNull($cotizacion->detalles->sole()->materiales->sole()->subtotal_aplicado);
        $this->postJson('/admin/cotizaciones/'.$cotizacion->id.'/aprobar', $this->precios($cotizacion))->assertUnprocessable();
        $this->assertNull($cotizacion->fresh()->aprobado_at);
        $datos['clave_registro'] = Str::uuid()->toString();
        $datos['detalles'][0]['materiales'][0]['costo_unitario_estimado'] = '0';
        $this->postJson('/admin/cotizaciones/create', $datos)->assertUnprocessable();
    }

    public function test_estado_retirado_y_costo_cero_no_se_aceptan(): void
    {
        $datos = $this->datos();
        $datos['detalles'][0]['materiales'][0]['estado_costo'] = 'gratuito';
        $datos['detalles'][0]['materiales'][0]['costo_unitario_estimado'] = '0';
        $this->actingAs($this->propietario)->postJson('/admin/cotizaciones/create', $datos)->assertUnprocessable();
        $datos['detalles'][0]['materiales'][0]['estado_costo'] = 'conocido';
        $this->postJson('/admin/cotizaciones/create', $datos)->assertUnprocessable();
        $this->assertSame(0, Cotizacion::count());
    }

    public function test_varios_muebles_precios_por_detalle_y_aprobacion_en_una_accion(): void
    {
        $datos = $this->datos();
        $datos['detalles'][] = $datos['detalles'][0];
        $datos['detalles'][1]['tipo_mueble_id'] = TipoMueble::where('codigo', 'silla')->value('id');
        $cotizacion = $this->crear($datos);
        $this->assertCount(2, $cotizacion->detalles);
        $precios = ['version' => 1, 'precios' => [
            $cotizacion->detalles[0]->id => '400.15', $cotizacion->detalles[1]->id => '390.10',
        ]];
        $this->postJson('/admin/cotizaciones/'.$cotizacion->id.'/aprobar', $precios)->assertRedirect();
        $cotizacion->refresh();
        $this->assertSame('790.25', $cotizacion->precio_final);
        $this->assertSame($this->propietario->id, $cotizacion->validado_por_id);
        $this->assertSame($this->propietario->id, $cotizacion->aprobado_por_id);
        $this->assertNotNull($cotizacion->validado_at);
        $this->assertSame('aprobada', $cotizacion->estado);
        $this->assertSame(0, MovimientoMaterial::count());
    }

    public function test_aprobada_no_se_modifica_y_pdf_usa_datos_congelados_sin_costos_internos(): void
    {
        $cotizacion = $this->crear();
        DB::table('cotizaciones')->where('id', $cotizacion->id)->update(['cliente_id' => $this->cliente->id]);
        $this->get('/admin/cotizaciones/'.$cotizacion->id.'/pdf')->assertForbidden();
        $this->post('/admin/cotizaciones/'.$cotizacion->id.'/aprobar', $this->precios($cotizacion))->assertRedirect();
        $cotizacion->refresh();
        $this->get('/admin/cotizaciones/'.$cotizacion->id.'/edit')->assertForbidden();
        $this->postJson('/admin/cotizaciones/'.$cotizacion->id.'/aprobar', $this->precios($cotizacion))->assertForbidden();
        $this->cliente->nombre = 'Nombre cambiado después';
        $this->cliente->save();
        app(CatalogoService::class)->guardar($this->propietario, [
            'nombre' => 'Nombre actualizado del catálogo', 'categoria_id' => $this->material->categoria_id,
            'unidad_id' => $this->material->unidad_id, 'precio_referencia' => '99', 'activo' => true,
        ], $this->material);
        $vistaPdf = null;
        View::composer('pdf.cotizacion', function ($view) use (&$vistaPdf) {
            $vistaPdf = $view->getData();
        });
        $this->actingAs($this->maestro);
        $response = $this->get('/admin/cotizaciones/'.$cotizacion->id.'/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertSame('Cliente aislado', $vistaPdf['cliente']['nombre']);
        $this->assertSame('Material de prueba', $vistaPdf['detalles'][0]['material_principal_nombre']);
        $this->assertStringNotContainsString('recargo', json_encode($vistaPdf));
        $this->assertStringNotContainsString('costo_', json_encode($vistaPdf));
        $this->assertSame('40.000000', $cotizacion->fresh()->detalles->sole()->materiales->sole()->costo_unitario_estimado);
        $this->assertSame('400.00', $cotizacion->fresh()->precio_final);
    }

    public function test_version_y_precios_no_permiten_aprobar_datos_distintos(): void
    {
        $cotizacion = $this->crear();
        $precios = $this->precios($cotizacion);
        $precios['version'] = 99;
        $this->postJson('/admin/cotizaciones/'.$cotizacion->id.'/aprobar', $precios)->assertUnprocessable();
        $precios = $this->precios($cotizacion);
        $precios['precios'][99999] = '1';
        $this->postJson('/admin/cotizaciones/'.$cotizacion->id.'/aprobar', $precios)->assertUnprocessable();
        $this->assertNull($cotizacion->fresh()->aprobado_at);
    }

    public function test_reenvio_creacion_no_duplica_y_cambio_con_misma_clave_se_rechaza(): void
    {
        $datos = $this->datos();
        $this->crear($datos);
        $this->postJson('/admin/cotizaciones/create', $datos)->assertRedirect();
        $this->assertSame(1, Cotizacion::count());
        $datos['detalles'][0]['cantidad_muebles'] = 4;
        $this->postJson('/admin/cotizaciones/create', $datos)->assertUnprocessable();
        $this->assertSame(1, Cotizacion::count());
    }

    public function test_error_revierte_todo_y_permite_reintentar_sin_filas_parciales(): void
    {
        $datos = $this->datos();
        $invalido = $datos;
        $invalido['detalles'][0]['materiales'][0]['costo_unitario_estimado'] = '-2';
        $this->actingAs($this->propietario)->postJson('/admin/cotizaciones/create', $invalido)->assertUnprocessable();
        $this->assertSame(0, Cotizacion::count());
        $this->assertSame(0, DetalleCotizacion::count());
        $this->assertSame(0, MaterialCotizacion::count());
        $this->crear($datos);
        $this->assertSame(1, Cotizacion::count());
    }

    public function test_no_cambia_unidad_o_borra_material_y_cliente_ya_cotizados(): void
    {
        $cotizacion = $this->crear();
        DB::table('cotizaciones')->where('id', $cotizacion->id)->update(['cliente_id' => $this->cliente->id]);
        $this->putJson('/admin/materiales/'.$this->material->id, [
            'nombre' => $this->material->nombre, 'categoria_id' => $this->material->categoria_id,
            'unidad_id' => UnidadMaterial::where('codigo', 'unidad')->value('id'), 'precio_referencia' => '40', 'activo' => true,
        ])->assertUnprocessable();
        $this->deleteJson('/admin/clientes/'.$this->cliente->id)->assertUnprocessable();
        $this->deleteJson('/admin/materiales/'.$this->material->id)->assertUnprocessable();
        $this->assertNotNull($this->cliente->fresh());
    }

    public function test_edita_con_ids_quita_detalle_y_no_pierde_filas_o_motivos(): void
    {
        $datos = $this->datos();
        $datos['detalles'][] = $datos['detalles'][0];
        $cotizacion = $this->crear($datos);
        $guardado = $cotizacion->detalles[1];
        $edicion = $datos;
        unset($edicion['clave_registro']);
        $edicion['version'] = 1;
        $edicion['detalles'] = [$datos['detalles'][1]];
        $edicion['detalles'][0]['id'] = $guardado->id;
        $edicion['detalles'][0]['materiales'][0]['id'] = $guardado->materiales->sole()->id;
        $this->putJson('/admin/cotizaciones/'.$cotizacion->id, $edicion)->assertRedirect();
        $this->assertSame($guardado->id, $cotizacion->fresh()->detalles->sole()->id);
        $this->assertSame(1, $cotizacion->fresh()->detalles->sole()->posicion);
        $this->putJson('/admin/cotizaciones/'.$cotizacion->id, $edicion)->assertUnprocessable();
    }

    public function test_dimensiones_costos_y_totales_fisicos_invalidos_se_rechazan(): void
    {
        foreach (['largo_cm' => '0', 'mano_obra_unitaria' => '1.001', 'cantidad_muebles' => '0'] as $campo => $valor) {
            $datos = $this->datos();
            $datos['detalles'][0][$campo] = $valor;
            $this->actingAs($this->propietario)->postJson('/admin/cotizaciones/create', $datos)->assertUnprocessable();
        }
        $datos = $this->datos();
        $datos['detalles'][0]['materiales'][0]['material_id'] = 999999;
        $this->postJson('/admin/cotizaciones/create', $datos)->assertUnprocessable();
        $this->assertSame(0, Cotizacion::count());
    }

    public function test_porcentaje_individual_no_se_hereda_y_propietario_sin_validar_no_aprueba(): void
    {
        $cotizacion = $this->crear(array_replace($this->datos(), ['recargo_porcentaje' => '0']));
        $otra = $this->crear(array_replace($this->datos(), ['recargo_porcentaje' => '50']));
        $nueva = $this->crear();
        $this->assertSame('50.0000', $otra->fresh()->recargo_porcentaje);
        $this->assertNull($nueva->recargo_porcentaje);
        $this->get('/admin/cotizaciones/create')->assertOk()->assertViewHas('cotizacion', fn ($cotizacion) => $cotizacion->recargo_porcentaje === null);
        $this->get('/admin/cotizaciones/configuracion')->assertNotFound();
        $this->assertSame('0.0000', $cotizacion->fresh()->recargo_porcentaje);
        $this->propietario->revokePermissionTo('cotizaciones.validar');
        Role::findByName('propietario')->revokePermissionTo('cotizaciones.validar');
        $this->postJson('/admin/cotizaciones/'.$cotizacion->id.'/aprobar', $this->precios($cotizacion))->assertForbidden();
    }

    public function test_redondeo_monetario_y_recargo_mayor_a_cien_sin_usar_margen_venta(): void
    {
        $datos = $this->datos();
        $datos['recargo_porcentaje'] = '150';
        $datos['detalles'][0]['mano_obra_unitaria'] = '0';
        $datos['detalles'][0]['otros_costos_total'] = '0';
        $datos['detalles'][0]['materiales'][0]['cantidad_unitaria'] = '1';
        $datos['detalles'][0]['materiales'][0]['costo_unitario_estimado'] = '0.335';
        $cotizacion = $this->crear($datos);
        $this->assertSame('1.01', $cotizacion->costo_total);
        $this->assertSame('2.53', $cotizacion->precio_sugerido);
    }

    public function test_fallo_de_auditoria_revierte_aprobacion_y_precios_completos(): void
    {
        $cotizacion = $this->crear();
        $this->instance(AuditoriaService::class, \Mockery::mock(AuditoriaService::class, function ($mock) {
            $mock->shouldReceive('registrar')->once()->andThrow(new \RuntimeException('Fallo controlado de auditoría'));
        }));
        try {
            app(CotizacionService::class)->aprobar($this->propietario, $cotizacion, $this->precios($cotizacion));
            $this->fail('Debía revertirse la aprobación.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Fallo controlado de auditoría', $error->getMessage());
        }
        $cotizacion->refresh();
        $this->assertNull($cotizacion->aprobado_at);
        $this->assertNull($cotizacion->validado_at);
        $this->assertNull($cotizacion->precio_final);
        $this->assertNull($cotizacion->detalles->sole()->precio_final);
        $this->assertSame(1, $cotizacion->version);
    }

    public function test_maestro_autorizado_aprueba_precio_sin_eludir_inmutabilidad(): void
    {
        $cotizacion = $this->crear();
        foreach (['cotizaciones.aprobar', 'cotizaciones.validar'] as $permiso) {
            app(PermisosUsuarioService::class)->modificar($this->propietario, $this->maestro, [
                Permission::findByName($permiso)->id => 'permitir',
            ]);
        }
        $this->actingAs($this->maestro->fresh())->post('/admin/cotizaciones/'.$cotizacion->id.'/aprobar', $this->precios($cotizacion))->assertRedirect();
        $this->assertSame($this->maestro->id, $cotizacion->fresh()->aprobado_por_id);
        $this->assertSame('400.00', $cotizacion->fresh()->precio_final);
        $this->get('/admin/cotizaciones/'.$cotizacion->id.'/edit')->assertForbidden();
        $this->postJson('/admin/cotizaciones/'.$cotizacion->id.'/aprobar', $this->precios($cotizacion->fresh()))->assertForbidden();
    }
}

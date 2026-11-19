<?php
use App\Models\{Cotizacion, User};
use App\Services\{CotizacionService, EliminacionService, PedidoService};
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
require __DIR__.'/../../vendor/autoload.php';
$app=require __DIR__.'/../../bootstrap/app.php';$app->make(Kernel::class)->bootstrap();
$base=DB::connection()->getDatabaseName();
if(!app()->environment('testing')||!preg_match('/^carpinteria_el_tajibo_ensayo_estados_\d{8}_\d{6}$/D',$base))throw new RuntimeException('Solo ensayo aislado');
$archivo=realpath($argv[1]??'');$raiz=realpath(storage_path('app/private/pruebas-estados-2026-11-16'));
if(!$archivo||!$raiz||!str_starts_with($archivo,$raiz.DIRECTORY_SEPARATOR))throw new RuntimeException('Entrada no autorizada');
$entrada=json_decode(file_get_contents($archivo),true,512,JSON_THROW_ON_ERROR);file_put_contents($archivo.'.ready','1');
$limite=microtime(true)+20;while(!is_file($entrada['continuar'])){if(microtime(true)>$limite)throw new RuntimeException('Sin señal de inicio');usleep(10000);}
try{
    $actor=User::findOrFail($entrada['actor']);
    if($entrada['accion']==='pedido')$resultado=app(PedidoService::class)->crear($actor,$entrada['datos']);
    else{
        $cotizacion=Cotizacion::findOrFail($entrada['cotizacion']);
        $resultado=match($entrada['accion']){
            'aprobar'=>app(CotizacionService::class)->aprobar($actor,$cotizacion,$entrada['datos']),
            'rechazar'=>app(CotizacionService::class)->rechazar($actor,$cotizacion,$entrada['datos']),
            'eliminar'=>app(EliminacionService::class)->eliminar($actor,$cotizacion,(int)$entrada['datos']['version']),
        };
    }
    echo json_encode(['accion'=>$entrada['accion'],'resultado'=>'ok']);
}catch(ValidationException $error){echo json_encode(['accion'=>$entrada['accion'],'resultado'=>'validacion','campos'=>array_keys($error->errors())]);}
catch(Illuminate\Auth\Access\AuthorizationException $error){echo json_encode(['accion'=>$entrada['accion'],'resultado'=>'denegado']);}
catch(Illuminate\Database\Eloquent\ModelNotFoundException $error){echo json_encode(['accion'=>$entrada['accion'],'resultado'=>'no_encontrado']);}

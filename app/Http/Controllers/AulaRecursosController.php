<?php

namespace App\Http\Controllers;

use App\Http\Requests\Storeaula_recursosRequest;
use App\Http\Requests\Updateaula_recursosRequest;
use App\Models\aula_recursos;

class AulaRecursoController extends Controller
{
   
    public function getAll()
    {
        $recursos = AulaRecurso::with(['aula', 'recursoTipo'])->get();
        return response()->json($recursos, 200);
    }


    public function getById($id)
    {
        $recurso = AulaRecurso::with(['aula', 'recursoTipo'])->find($id);
        
        if (!$recurso) {
            return response()->json(['message' => 'Recurso no encontrado'], 404);
        }
        
        return response()->json($recurso, 200);
    }


    public function create(Request $request)
    {
        $request->validate([
            'aula_id' => 'required|exists:aulas,id',
            'recurso_tipo_id' => 'required|exists:recursos_tipo,id',
            'cantidad' => 'required|integer|min:1',
            'estado' => 'required|in:operativo,en_reparacion,fuera_de_servicio',
            'observaciones' => 'nullable|string'
        ]);

        $recurso = AulaRecurso::create($request->all());

        return response()->json($recurso, 201);
    }

    public function update(Request $request, $id)
    {
        $recurso = AulaRecurso::find($id);
        
        if (!$recurso) {
            return response()->json(['message' => 'Recurso no encontrado'], 404);
        }

        $request->validate([
            'cantidad' => 'sometimes|integer|min:1',
            'estado' => 'sometimes|in:operativo,en_reparacion,fuera_de_servicio',
            'observaciones' => 'nullable|string'
        ]);

        $recurso->update($request->all());
        
        return response()->json($recurso, 200);
    }


    public function delete($id)
    {
        $recurso = AulaRecurso::find($id);
        
        if (!$recurso) {
            return response()->json(['message' => 'Recurso no encontrado'], 404);
        }

        $recurso->delete();
        
        return response()->json(['message' => 'Recurso eliminado exitosamente'], 204);
    }


    public function getByAula($aula_id)
    {
        $recursos = AulaRecurso::with(['recursoTipo'])
            ->where('aula_id', $aula_id)
            ->get();
        
        return response()->json($recursos, 200);
    }


    public function getByRecurso($recurso_tipo_id)
    {
        $recursos = AulaRecurso::with(['aula'])
            ->where('recurso_tipo_id', $recurso_tipo_id)
            ->get();
        
        return response()->json($recursos, 200);
    }


    public function getByStatus($estado)
    {
        $recursos = AulaRecurso::with(['aula', 'recursoTipo'])
            ->where('estado', $estado)
            ->get();
        
        return response()->json($recursos, 200);
    }

    public function getDisponiblesByAula($aula_id)
    {
        $recursos = AulaRecurso::with(['recursoTipo'])
            ->where('aula_id', $aula_id)
            ->where('estado', 'operativo')
            ->get();
        
        return response()->json($recursos, 200);
    }

    public function buscarPorRecursos(Request $request)
    {
        $request->validate([
            'recursos' => 'required|array',
            'recursos.*' => 'exists:recursos_tipo,id',
            'cantidad_minima' => 'sometimes|integer|min:1'
        ]);

        $cantidadMinima = $request->get('cantidad_minima', 1);

        $aulas = DB::table('aulas')
            ->join('aula_recursos', 'aulas.id', '=', 'aula_recursos.aula_id')
            ->whereIn('aula_recursos.recurso_tipo_id', $request->recursos)
            ->where('aula_recursos.cantidad', '>=', $cantidadMinima)
            ->where('aula_recursos.estado', 'operativo')
            ->select('aulas.*')
            ->groupBy('aulas.id')
            ->havingRaw('COUNT(DISTINCT aula_recursos.recurso_tipo_id) = ?', [count($request->recursos)])
            ->get();

        return response()->json($aulas, 200);
    }


    public function cambiarEstado(Request $request, $id)
    {
        $recurso = AulaRecurso::find($id);
        
        if (!$recurso) {
            return response()->json(['message' => 'Recurso no encontrado'], 404);
        }

        $request->validate([
            'estado' => 'required|in:operativo,en_reparacion,fuera_de_servicio',
            'observaciones' => 'nullable|string'
        ]);

        $recurso->estado = $request->estado;
        
        if ($request->has('observaciones')) {
            $recurso->observaciones = $request->observaciones;
        }
        
        $recurso->save();
        
        return response()->json($recurso, 200);
    }


    public function getInventario(Request $request)
    {
        $query = DB::table('aula_recursos')
            ->join('aulas', 'aula_recursos.aula_id', '=', 'aulas.id')
            ->join('recursos_tipo', 'aula_recursos.recurso_tipo_id', '=', 'recursos_tipo.id')
            ->select(
                'aulas.codigo as aula_codigo',
                'aulas.nombre as aula_nombre',
                'recursos_tipo.nombre as recurso_nombre',
                'aula_recursos.cantidad',
                'aula_recursos.estado',
                'aula_recursos.observaciones'
            );

        if ($request->has('recurso_tipo_id')) {
            $query->where('aula_recursos.recurso_tipo_id', $request->recurso_tipo_id);
        }

        if ($request->has('aula_id')) {
            $query->where('aula_recursos.aula_id', $request->aula_id);
        }

        $inventario = $query->get();

        return response()->json($inventario, 200);
    }
}

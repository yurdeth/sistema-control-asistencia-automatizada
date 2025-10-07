<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreaulasRequest;
use App\Http\Requests\UpdateaulasRequest;
use App\Models\aulas;

class AulaController extends Controller
{
  
    public function index()
    {
        $aulas = Aula::with(['recursos'])->get();
        return response()->json($aulas, 200);
    }

    
    public function show($id)
    {
        $aula = Aula::with(['recursos.recursoTipo'])->find($id);
        
        if (!$aula) {
            return response()->json(['message' => 'Aula no encontrada'], 404);
        }
        
        return response()->json($aula, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|max:50|unique:aulas,codigo',
            'nombre' => 'required|string|max:100',
            'capacidad_pupitres' => 'required|integer|min:1',
            'ubicacion' => 'required|string|max:255',
            'qr_code' => 'required|string|max:255|unique:aulas,qr_code',
            'estado' => 'required|in:disponible,ocupada,mantenimiento,inactiva'
        ]);

        $aula = Aula::create($request->all());

        return response()->json($aula, 201);
    }

    public function edit(Request $request, $id)
    {
        $aula = Aula::find($id);
        
        if (!$aula) {
            return response()->json(['message' => 'Aula no encontrada'], 404);
        }

        $request->validate([
            'codigo' => 'sometimes|string|max:50|unique:aulas,codigo,' . $id,
            'nombre' => 'sometimes|string|max:100',
            'capacidad_pupitres' => 'sometimes|integer|min:1',
            'ubicacion' => 'sometimes|string|max:255',
            'estado' => 'sometimes|in:disponible,ocupada,mantenimiento,inactiva'
        ]);

        $aula->update($request->all());
        
        return response()->json($aula, 200);
    }


    public function destroy($id)
    {
        $aula = Aula::find($id);
        
        if (!$aula) {
            return response()->json(['message' => 'Aula no encontrada'], 404);
        }

        $aula->delete();
        
        return response()->json(['message' => 'Aula eliminada exitosamente'], 204);
    }

    public function getClassroomByCode($codigo)
    {
        $aula = Aula::with(['recursos'])->where('codigo', $codigo)->first();
        
        if (!$aula) {
            return response()->json(['message' => 'Aula no encontrada'], 404);
        }
        
        return response()->json($aula, 200);
    }


    public function getClassroomsByStatus($estado)
    {
        $aulas = Aula::where('estado', $estado)->get();
        return response()->json($aulas, 200);
    }

  
    public function getClassroomsByMinCapacity($capacidad)
    {
        $aulas = Aula::where('capacidad_pupitres', '>=', $capacidad)->get();
        return response()->json($aulas, 200);
    }

    public function getAvailableClassrooms()
    {
        $aulas = Aula::where('estado', 'disponible')->get();
        return response()->json($aulas, 200);
    }

    public function getClassroomsByLocation($ubicacion)
    {
        $aulas = Aula::where('ubicacion', 'LIKE', "%{$ubicacion}%")->get();
        return response()->json($aulas, 200);
    }

    public function getClassroomByQrCode($qr_code)
    {
        $aula = Aula::where('qr_code', $qr_code)->first();
        
        if (!$aula) {
            return response()->json(['message' => 'Aula no encontrada'], 404);
        }
        
        return response()->json($aula, 200);
    }

    public function changeClassroomStatus(Request $request, $id)
    {
        $aula = Aula::find($id);
        
        if (!$aula) {
            return response()->json(['message' => 'Aula no encontrada'], 404);
        }

        $request->validate([
            'estado' => 'required|in:disponible,ocupada,mantenimiento,inactiva'
        ]);

        $aula->estado = $request->estado;
        $aula->save();
        
        return response()->json($aula, 200);
    }

    public function getClassroomStatistics(Request $request, $id)
    {
        $aula = Aula::find($id);
        
        if (!$aula) {
            return response()->json(['message' => 'Aula no encontrada'], 404);
        }

        $query = DB::table('estadisticas_aulas_diarias')
            ->where('aula_id', $id);

        if ($request->has('fecha_inicio')) {
            $query->where('fecha', '>=', $request->fecha_inicio);
        }

        if ($request->has('fecha_fin')) {
            $query->where('fecha', '<=', $request->fecha_fin);
        }

        $estadisticas = $query->get();
        
        return response()->json([
            'aula' => $aula,
            'estadisticas' => $estadisticas
        ], 200);
    }

    public function getClassroomSuggestions(Request $request)
    {
        $request->validate([
            'capacidad_minima' => 'required|integer',
            'dia_semana' => 'required|in:lunes,martes,miercoles,jueves,viernes,sabado,domingo',
            'hora_inicio' => 'required|date_format:H:i:s',
            'hora_fin' => 'required|date_format:H:i:s'
        ]);

        $sugerencias = DB::select('CALL sp_sugerir_aula(?, ?, ?, ?)', [
            $request->capacidad_minima,
            $request->dia_semana,
            $request->hora_inicio,
            $request->hora_fin
        ]);

        return response()->json($sugerencias, 200);
    }
}

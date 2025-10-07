<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoregruposRequest;
use App\Http\Requests\UpdategruposRequest;
use App\Models\grupos;

class GrupoController extends Controller
{
   
    public function index()
    {
        $grupos = Grupo::with(['materia', 'ciclo', 'docente'])->get();
        return response()->json($grupos, 200);
    }

  
    public function show($id)
    {
        $grupo = Grupo::with(['materia', 'ciclo', 'docente', 'horarios'])->find($id);
        
        if (!$grupo) {
            return response()->json(['message' => 'Grupo no encontrado'], 404);
        }
        
        return response()->json($grupo, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'materia_id' => 'required|exists:materias,id',
            'ciclo_id' => 'required|exists:ciclos_academicos,id',
            'docente_id' => 'required|exists:usuarios,id',
            'numero_grupo' => 'required|string|max:10',
            'capacidad_maxima' => 'required|integer|min:1',
            'estado' => 'required|in:activo,finalizado,cancelado'
        ]);

        $grupo = Grupo::create([
            'materia_id' => $request->materia_id,
            'ciclo_id' => $request->ciclo_id,
            'docente_id' => $request->docente_id,
            'numero_grupo' => $request->numero_grupo,
            'capacidad_maxima' => $request->capacidad_maxima,
            'estudiantes_inscritos' => 0,
            'estado' => $request->estado
        ]);

        return response()->json($grupo, 201);
    }

   
    public function edit(Request $request, $id)
    {
        $grupo = Grupo::find($id);
        
        if (!$grupo) {
            return response()->json(['message' => 'Grupo no encontrado'], 404);
        }

        $request->validate([
            'materia_id' => 'sometimes|exists:materias,id',
            'ciclo_id' => 'sometimes|exists:ciclos_academicos,id',
            'docente_id' => 'sometimes|exists:usuarios,id',
            'numero_grupo' => 'sometimes|string|max:10',
            'capacidad_maxima' => 'sometimes|integer|min:1',
            'estado' => 'sometimes|in:activo,finalizado,cancelado'
        ]);

        $grupo->update($request->all());
        
        return response()->json($grupo, 200);
    }

  
    public function destroy($id)
    {
        $grupo = Grupo::find($id);
        
        if (!$grupo) {
            return response()->json(['message' => 'Grupo no encontrado'], 404);
        }

        $grupo->delete();
        
        return response()->json(['message' => 'Grupo eliminado exitosamente'], 204);
    }


    public function getGroupsBySubject($id)
    {
        $grupos = Grupo::with(['ciclo', 'docente'])
            ->where('materia_id', $id)
            ->get();
        
        return response()->json($grupos, 200);
    }


    public function getGroupsByCycle($id)
    {
        $grupos = Grupo::with(['materia', 'docente'])
            ->where('ciclo_id', $id)
            ->get();
        
        return response()->json($grupos, 200);
    }

  
    public function getGroupsByProfessor($id)
    {
        $grupos = Grupo::with(['materia', 'ciclo'])
            ->where('docente_id', $id)
            ->get();
        
        return response()->json($grupos, 200);
    }

    
    public function getGroupsByStatus($estado)
    {
        $grupos = Grupo::with(['materia', 'ciclo', 'docente'])
            ->where('estado', $estado)
            ->get();
        
        return response()->json($grupos, 200);
    }

   
    public function getAvailableGroups()
    {
        $grupos = Grupo::with(['materia', 'ciclo', 'docente'])
            ->whereColumn('estudiantes_inscritos', '<', 'capacidad_maxima')
            ->where('estado', 'activo')
            ->get();
        
        return response()->json($grupos, 200);
    }

  
    public function getGroupsByNumber(Request $request, $numero_grupo)
    {
        $query = Grupo::with(['materia', 'ciclo', 'docente'])
            ->where('numero_grupo', $numero_grupo);

        if ($request->has('materia_id')) {
            $query->where('materia_id', $request->materia_id);
        }

        if ($request->has('ciclo_id')) {
            $query->where('ciclo_id', $request->ciclo_id);
        }

        $grupos = $query->get();
        
        return response()->json($grupos, 200);
    }
}

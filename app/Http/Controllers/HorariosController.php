<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorehorariosRequest;
use App\Http\Requests\UpdatehorariosRequest;
use App\Models\horarios;

class HorarioController extends Controller
{

    public function getAll()
    {
        $horarios = Horario::with(['grupo.materia', 'aula'])->get();
        return response()->json($horarios, 200);
    }

    public function getById($id)
    {
        $horario = Horario::with(['grupo.materia', 'grupo.docente', 'aula'])->find($id);
        
        if (!$horario) {
            return response()->json(['message' => 'Horario no encontrado'], 404);
        }
        
        return response()->json($horario, 200);
    }

    public function create(Request $request)
    {
        $request->validate([
            'grupo_id' => 'required|exists:grupos,id',
            'aula_id' => 'required|exists:aulas,id',
            'dia_semana' => 'required|in:lunes,martes,miercoles,jueves,viernes,sabado,domingo',
            'hora_inicio' => 'required|date_format:H:i:s',
            'hora_fin' => 'required|date_format:H:i:s|after:hora_inicio'
        ]);

        $conflicto = Horario::where('aula_id', $request->aula_id)
            ->where('dia_semana', $request->dia_semana)
            ->where(function($query) use ($request) {
                $query->whereBetween('hora_inicio', [$request->hora_inicio, $request->hora_fin])
                    ->orWhereBetween('hora_fin', [$request->hora_inicio, $request->hora_fin])
                    ->orWhere(function($q) use ($request) {
                        $q->where('hora_inicio', '<=', $request->hora_inicio)
                          ->where('hora_fin', '>=', $request->hora_fin);
                    });
            })
            ->exists();

        if ($conflicto) {
            return response()->json(['message' => 'Conflicto de horario detectado'], 409);
        }

        $horario = Horario::create($request->all());

        return response()->json($horario, 201);
    }


    public function update(Request $request, $id)
    {
        $horario = Horario::find($id);
        
        if (!$horario) {
            return response()->json(['message' => 'Horario no encontrado'], 404);
        }

        $request->validate([
            'grupo_id' => 'sometimes|exists:grupos,id',
            'aula_id' => 'sometimes|exists:aulas,id',
            'dia_semana' => 'sometimes|in:lunes,martes,miercoles,jueves,viernes,sabado,domingo',
            'hora_inicio' => 'sometimes|date_format:H:i:s',
            'hora_fin' => 'sometimes|date_format:H:i:s'
        ]);

        $horario->update($request->all());
        
        return response()->json($horario, 200);
    }


    public function delete($id)
    {
        $horario = Horario::find($id);
        
        if (!$horario) {
            return response()->json(['message' => 'Horario no encontrado'], 404);
        }

        $horario->delete();
        
        return response()->json(['message' => 'Horario eliminado exitosamente'], 204);
    }


    public function getByGrupo($grupo_id)
    {
        $horarios = Horario::with(['aula'])
            ->where('grupo_id', $grupo_id)
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get();
        
        return response()->json($horarios, 200);
    }

    public function getByAula($aula_id)
    {
        $horarios = Horario::with(['grupo.materia', 'grupo.docente'])
            ->where('aula_id', $aula_id)
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get();
        
        return response()->json($horarios, 200);
    }

    public function getByDia($dia_semana)
    {
        $horarios = Horario::with(['grupo.materia', 'aula'])
            ->where('dia_semana', $dia_semana)
            ->orderBy('hora_inicio')
            ->get();
        
        return response()->json($horarios, 200);
    }

    public function getConflictos($grupo_id)
    {
        $grupo = Grupo::find($grupo_id);
        
        if (!$grupo) {
            return response()->json(['message' => 'Grupo no encontrado'], 404);
        }

        $conflictos = DB::table('horarios as h1')
            ->join('grupos as g1', 'h1.grupo_id', '=', 'g1.id')
            ->join('horarios as h2', function($join) use ($grupo) {
                $join->on('h1.dia_semana', '=', 'h2.dia_semana')
                     ->where('h2.grupo_id', '=', $grupo_id);
            })
            ->join('grupos as g2', 'h2.grupo_id', '=', 'g2.id')
            ->where('g1.docente_id', $grupo->docente_id)
            ->where('h1.grupo_id', '!=', $grupo_id)
            ->where(function($query) {
                $query->whereBetween('h1.hora_inicio', [DB::raw('h2.hora_inicio'), DB::raw('h2.hora_fin')])
                    ->orWhereBetween('h1.hora_fin', [DB::raw('h2.hora_inicio'), DB::raw('h2.hora_fin')]);
            })
            ->select('h1.*', 'g1.numero_grupo')
            ->get();

        return response()->json($conflictos, 200);
    }


    public function getDisponibilidadAula(Request $request, $aula_id)
    {
        $aula = Aula::find($aula_id);
        
        if (!$aula) {
            return response()->json(['message' => 'Aula no encontrada'], 404);
        }

        $query = Horario::where('aula_id', $aula_id);

        if ($request->has('dia_semana')) {
            $query->where('dia_semana', $request->dia_semana);
        }

        $horarios = $query->orderBy('dia_semana')->orderBy('hora_inicio')->get();

        return response()->json([
            'aula' => $aula,
            'horarios_ocupados' => $horarios
        ], 200);
    }


    public function getByRango(Request $request)
    {
        $request->validate([
            'hora_inicio' => 'required|date_format:H:i:s',
            'hora_fin' => 'required|date_format:H:i:s'
        ]);

        $query = Horario::with(['grupo.materia', 'aula'])
            ->where(function($q) use ($request) {
                $q->whereBetween('hora_inicio', [$request->hora_inicio, $request->hora_fin])
                  ->orWhereBetween('hora_fin', [$request->hora_inicio, $request->hora_fin]);
            });

        if ($request->has('dia_semana')) {
            $query->where('dia_semana', $request->dia_semana);
        }

        $horarios = $query->get();

        return response()->json($horarios, 200);
    }
}

<?php

namespace App;

enum RolesEnum: string {
    case ROOT = 'root';
    case ADMINISTRADOR_ACADEMICO = 'administrador_academico';
    case JEFE_DEPARTAMENTO = 'jefe_departamento';
    case COORDINADOR_CARRERAS = 'coordinador_carreras';
    case DOCENTE = 'docente';
    case ESTUDIANTE = 'estudiante';
    case INVITADO = 'invitado';
}

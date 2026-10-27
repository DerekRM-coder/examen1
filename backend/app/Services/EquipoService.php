<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Equipo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EquipoService
{
    public function listar(?string $q, ?string $estado, int $porPagina): LengthAwarePaginator
    {
        $consulta = Equipo::query()->with('categoria');

        if ($q !== null && $q !== '') {
            $consulta->where(function ($consulta) use ($q): void {
                $consulta
                    ->where('codigo', 'like', "%{$q}%")
                    ->orWhere('nombre', 'like', "%{$q}%");
            });
        }

        if ($estado !== null && $estado !== '') {
            $consulta->where('estado', $estado);
        }

        return $consulta->orderBy('codigo')->paginate($porPagina);
    }

    public function crear(array $datos): Equipo
    {
        return Equipo::create($datos)->load('categoria');
    }

    public function actualizar(Equipo $equipo, array $datos): Equipo
    {
        $equipo->update($datos);

        return $equipo->load('categoria');
    }

    public function eliminar(Equipo $equipo): void
    {
        if ($equipo->estado === 'prestado') {
            throw new ReglaNegocioException('No se puede eliminar un equipo prestado.');
        }

        $equipo->delete();
    }

    public function prestar(Equipo $equipo): Equipo
    {
        if ($equipo->estado === 'mantenimiento') {
            throw new ReglaNegocioException('No se puede prestar un equipo en mantenimiento.');
        }

        if ($equipo->estado === 'prestado') {
            throw new ReglaNegocioException('El equipo ya se encuentra prestado.');
        }

        $equipo->update(['estado' => 'prestado']);

        return $equipo->load('categoria');
    }
}

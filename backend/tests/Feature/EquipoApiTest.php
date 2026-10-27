<?php

use App\Models\Categoria;
use App\Models\Equipo;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create());
});

it('crea un equipo y responde 201 con el encabezado Location', function () {
    $categoria = Categoria::factory()->create();
    $datos = [
        'codigo'            => 'EQ-9001',
        'nombre'            => 'Laptop para pruebas',
        'categoria_id'      => $categoria->id,
        'estado'            => 'disponible',
        'fecha_adquisicion' => now()->subDay()->toDateString(),
        'costo_interno'     => 125000.00,
    ];

    $respuesta = $this->postJson('/api/equipos', $datos);
    $id = $respuesta->json('data.id');

    $respuesta
        ->assertCreated()
        ->assertHeader('Location', route('equipos.show', $id))
        ->assertJsonStructure([
            'data' => [
                'id',
                'codigo',
                'nombre',
                'estado',
                'fecha_adquisicion',
                'categoria' => ['id', 'nombre'],
            ],
        ])
        ->assertJsonPath('data.codigo', 'EQ-9001')
        ->assertJsonMissingPath('data.costo_interno');

    $this->assertDatabaseHas('equipos', [
        'id'     => $id,
        'codigo' => 'EQ-9001',
    ]);
});

it('rechaza un equipo con datos inválidos y responde 422 con errores por campo', function () {
    $respuesta = $this->postJson('/api/equipos', [
        'codigo'            => 'codigo-invalido',
        'nombre'            => 'AB',
        'categoria_id'      => 999999,
        'estado'            => 'prestado',
        'fecha_adquisicion' => now()->addDay()->toDateString(),
        'costo_interno'     => -1,
    ]);

    $respuesta
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'codigo',
            'nombre',
            'categoria_id',
            'estado',
            'fecha_adquisicion',
            'costo_interno',
        ]);
});

it('impide eliminar un equipo prestado y responde 409', function () {
    $equipo = Equipo::factory()->prestado()->create();

    $this->deleteJson("/api/equipos/{$equipo->id}")
        ->assertStatus(409)
        ->assertJson([
            'mensaje' => 'No se puede eliminar un equipo prestado.',
            'codigo'  => 'REGLA_NEGOCIO',
        ]);

    $this->assertDatabaseHas('equipos', [
        'id'     => $equipo->id,
        'estado' => 'prestado',
    ]);
});

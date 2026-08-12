<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSchoolClassRequest;
use App\Http\Requests\UpdateSchoolClassRequest;
use App\Http\Resources\SchoolClassResource;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class SchoolClassController extends Controller
{
    
    public function index()
    {
        $classes = SchoolClass::with('waliKelas.user')->get();

        return SchoolClassResource::collection($classes);
    }

    public function store(StoreSchoolClassRequest $request)
    {
        $class = SchoolClass::create([
            'name' => $request->name,
            'wali_kelas_id' => $request->wali_kelas_id,
        ]);

        return response()->json([
            'message' => 'Kelas berhasil dibuat',
            'data' => $class->load('waliKelas'),
        ], 201);
    }

    public function show(SchoolClass $schoolClass)
    {
        $schoolClass->load('waliKelas.user');

        return new SchoolClassResource($schoolClass);
    }
    public function update(UpdateSchoolClassRequest $request, SchoolClass $schoolClass) 
    {
        $schoolClass->update([
            'name' => $request->name,
            'wali_kelas_id' => $request->wali_kelas_id,
        ]);

        return new SchoolClassResource(
            $schoolClass->load('waliKelas.user')
        );
    }

    public function deactivate(SchoolClass $schoolClass)
    {
        $schoolClass->update(['is_active' => false]);

        return new SchoolClassResource(
        $schoolClass->load('waliKelas.user')
    );
    }

    public function activate(SchoolClass $schoolClass)
    {
        $schoolClass->update([
            'is_active' => true,
        ]);

        return new SchoolClassResource(
            $schoolClass->load('waliKelas.user')
        );
    }
}

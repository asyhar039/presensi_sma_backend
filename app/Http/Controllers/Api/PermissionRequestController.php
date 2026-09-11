<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\RejectPermissionRequest;
use App\Models\PermissionRequest;
use Illuminate\Http\JsonResponse;

class PermissionRequestController extends Controller
{
    public function index(): JsonResponse
    {
        $student = auth()->user()->student;

        if(!$student){
            return response()->json([
                'message' => 'Akun ini bukan milik siswa',
            ], 403);
        }
        $permission = PermissionRequest::where('student_id', $student->id)
        ->latest()
        ->get();

        return response()->json([
            'message' => 'Daftar pengajuan izin berhasil diambil',
            'data' => $permission,
        ]);
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $student = auth()->user()->student;

        if(!$student) {
            return response()->json([
                'message' => 'Akun ini bukan akun siswa',
            ], 403);
        }

        $documentPath = request()->file('document')->store('permission-documents', 'public');

        $permission = \App\Models\PermissionRequest::create([
            'student_id' => $student->id,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'document_path' => $documentPath,
        ]);

        return response()->json([
            'message' => 'Pengajuan izin sudah dibuat',
            'data' => $permission,
        ], 201);
    }

    public function indexForWaliKelas(): JsonResponse
    {
        $teacher =  auth()->user()->teacher;

        if(!$teacher){
            return response()->json([
                'message' => 'Akun ini bukan akun guru',
            ], 403);
        }

        $class = $teacher->schoolClass;

        if(!$class){
            return response()->json([
                'message' => 'Anda bukan wali kelas',
            ], 403);
        }

        $permission = PermissionRequest::whereHas('student', function ($query) use ($class) {
            $query->where('class_id', $class->id);
        })
        ->with([
            'student.user',
            'approvedBy.user',
        ])
        ->latest()
        ->get();

        return response()->json([
            'message' => 'Daftar pengajuan izin berhasil diambil',
            'data' => $permission,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $teacher = auth()->user()->teacher;

        if(!$teacher){
            return response()->json([
                'message' => 'Akun ini bukan akun guru,'
            ], 403);
        }

        $class = $teacher->schoolClass;

        if(!$class){
            return response()->json([
                'message' => 'Anda bukan wali kelas',
            ], 403);
        }

        $permission = PermissionRequest::with([
            'student.user',
            'approvedBy.user',
        ])
        ->where('id', $id)
        ->whereHas('student', function ($query) use ($class) {
            $query->where('class_id', $class->id);
        })
        ->first();

        if(!$permission){
            return response()->json([
                'message' => 'Pengajuan izin tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'message' => 'Detail pengajuan izin berhasil diambil',
            'data' => $permission,
        ]);
    }

    public function approve(int $id): JsonResponse
    {
        $teacher = auth()->user()->teacher;

        if(!$teacher){
            return response()->json([
                'message' => 'Akun ini bukan akun guru',
            ], 403);
        }

        $class = $teacher->schoolClass;

        if(!$class) {
            return response()->json([
                'message' => 'Anda bukan wali kelas',
            ], 403);
        }

        $permission = PermissionRequest::where('id', $id)
        ->whereHas('student', function ($query) use ($class) {
            $query->where('class_id', $class->id);
        })
        ->first();

        if(!$permission){
            return response()->json([
                'message' => 'Pengajuan izin tidak ditemukan',
            ], 404);
        }

        if($permission->status !== 'pending'){
            return response()->json([
                'message' => 'Pengajuan izin sudah diproses',
            ], 422);
        }

        $permission->update([
            'status' => 'approved',
            'approved_by' => $teacher->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return response()->json([
            'message' => 'Pengajuan izin berhasil disetujui',
            'data' => $permission->load([
                'student.user',
                'approvedBy.user',
            ]),
        ]);
    }

    public function reject(RejectPermissionRequest $request, int $id): JsonResponse
    {
        $teacher = auth()->user()->teacher;

        if(!$teacher) {
            return response()->json([
                'message' => 'Akun ini bukan akun guru',
            ], 403);
        }

        $class = $teacher->schoolClass;

        if(!$class) {
            return response()->json([
                'message' => 'Anda bukan wali kelas',
            ], 403);
        }

        $permission = PermissionRequest::where('id', $id)
        ->whereHas('student', function ($query) use ($class) {
            $query->where('class_id', $class->id);
        })
        ->first();

        if(!$permission){
            return response()->json([
                'message' => 'Pengajuan izin tidak ditemukan',
            ], 404);
        }

        if($permission->status !== 'pending'){
            return response()->json([
                'message' => 'Pengajuan izin sudah diproses',
            ], 422);
        }

        $permission->update([
            'status' => 'rejected',
            'approved_by' => $teacher->id,
            'approved_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json([
            'message' => 'Pengajuan izin berhasil ditolak',
            'data' => $permission->load([
                'student.user',
                'approvedBy.user',
            ]),
        ]);
    }
}

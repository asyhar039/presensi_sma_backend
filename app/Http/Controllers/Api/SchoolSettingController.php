<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSchoolSettingRequest;
use App\Models\SchoolSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SchoolSettingController extends Controller
{
    public function show(): JsonResponse
    {
        $schoolSetting = SchoolSetting::first();

        if (!$schoolSetting) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengaturan sekolah belum tersedia',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $schoolSetting,
        ]);
    }

    public function update(UpdateSchoolSettingRequest $request): JsonResponse
    {
        $schoolSetting = SchoolSetting::first();

        if (!$schoolSetting) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengaturan sekolah belum tersedia',
            ], 404);
        }

        $schoolSetting->update([
            'school_name' => $request->school_name,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius' => $request->radius,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengaturan sekolah berhasil diperbarui',
            'data' => $schoolSetting->fresh(),
        ]);
    }
}

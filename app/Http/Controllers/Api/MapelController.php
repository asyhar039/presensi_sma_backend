<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMapelRequest;
use App\Http\Requests\UpdateMapelRequest;
use App\Http\Resources\MapelResource;
use App\Models\Mapel;
use Illuminate\Http\Request;

class MapelController extends Controller
{
    public function index()
    {
        $mapels = Mapel::all();
        return MapelResource::collection($mapels);
    }
    
    public function store(StoreMapelRequest $request)
    {
        $mapel = Mapel::create([
            'code' => $request->code,
            'name' => $request->name,
        ]);

        return new MapelResource($mapel->fresh());
    }

    public function show(Mapel $mapel)
    {
        return new MapelResource($mapel);
    }

    public function update(UpdateMapelRequest $request, Mapel $mapel)
    {
        $mapel->update([
            'code' => $request->code,
            'name' => $request->name,
        ]);

        return new MapelResource($mapel->fresh());
    }

    public function destroy(Mapel $mapel)
    {
        $mapel->delete();
        return response()->json(['message' => 'Mata Pelajaran Berhasil dihapus.']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kbli2025Hierarchy;
use App\Models\PgKBLI2025;

class KbliHierarchyController extends Controller
{
    public function getChildren(Request $request)
    {
        $parentKode = $request->query('parent');

        if (empty($parentKode) || $parentKode === 'null') {
            // Get Kategori (Level A-U)
            $nodes = Kbli2025Hierarchy::whereNull('parent_kode')
                ->orderBy('kode')
                ->get();
            return response()->json($nodes);
        }

        // If length is 4, it means we are looking for 5-digit children (kelompok)
        // Which are stored in the PgKBLI2025 table
        if (strlen($parentKode) >= 4) {
            $children = PgKBLI2025::where('kode', 'like', $parentKode . '_')
                ->orderBy('kode')
                ->get()
                ->map(function ($item) {
                    return [
                        'kode' => $item->kode,
                        'judul' => $item->judul,
                        'deskripsi' => $item->deskripsi,
                        'contoh_lapangan' => $item->contoh_lapangan,
                        'level' => 'kelompok',
                        'is_leaf' => true
                    ];
                });
            return response()->json($children);
        }

        // Otherwise, fetch from the hierarchy table
        $nodes = Kbli2025Hierarchy::where('parent_kode', $parentKode)
            ->orderBy('kode')
            ->get()
            ->map(function ($item) {
                return [
                    'kode' => $item->kode,
                    'judul' => $item->judul,
                    'deskripsi' => $item->deskripsi,
                    'level' => $item->level,
                    'is_leaf' => false
                ];
            });

        return response()->json($nodes);
    }
}

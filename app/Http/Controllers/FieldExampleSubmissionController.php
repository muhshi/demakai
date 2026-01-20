<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\FieldExampleSubmission;

class FieldExampleSubmissionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'kode' => 'required|string',
            'content' => 'required|string|min:3',
        ]);

        FieldExampleSubmission::create([
            'type' => $validated['type'],
            'kode' => $validated['kode'],
            'content' => $validated['content'],
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Terima kasih! Pengajuan Anda akan diverifikasi oleh admin.']);
    }
}

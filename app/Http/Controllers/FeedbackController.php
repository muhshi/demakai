<?php

namespace App\Http\Controllers;

use App\Models\UserFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{
    /**
     * Store new feedback submitted by user.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|min:5|max:3000',
            'category' => 'nullable|string|in:saran_fitur,koreksi_hasil,kendala_bug,umum',
            'sender_name' => 'nullable|string|max:100',
            'sender_contact' => 'nullable|string|max:100',
            'search_query' => 'nullable|string|max:255',
        ], [
            'message.required' => 'Mohon tuliskan pesan saran atau masukan Anda.',
            'message.min' => 'Pesan terlalu pendek (minimal 5 karakter).',
            'message.max' => 'Pesan maksimal 3000 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $feedback = UserFeedback::create([
            'category' => $request->input('category', 'saran_fitur') ?: 'saran_fitur',
            'message' => trim($request->input('message')),
            'sender_name' => $request->input('sender_name') ? trim($request->input('sender_name')) : null,
            'sender_contact' => $request->input('sender_contact') ? trim($request->input('sender_contact')) : null,
            'search_query' => $request->input('search_query') ? trim($request->input('search_query')) : null,
            'status' => 'baru',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Terima kasih banyak atas saran dan masukan Anda! Kami akan meninjaunya untuk pengembangan berkelanjutan PINTAR KBLI.',
            'data' => [
                'id' => $feedback->id,
            ],
        ]);
    }
}

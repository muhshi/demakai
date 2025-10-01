<div class="space-y-4">
    {{-- Header Section --}}
    <div class="border-b pb-3">
        <h3 class="text-lg font-semibold">Hasil Parsing Dokumen</h3>
        <p class="text-sm text-gray-500">Preview hasil parsing dari file yang diupload</p>
    </div>

    {{-- Content Section (scrollable) --}}
    <div class="overflow-y-auto" style="max-height: calc(80vh - 160px);">
        <div class="prose max-w-none bg-white p-6 rounded-lg text-gray-800 leading-relaxed">
            {!! $content !!}
        </div>
    </div>

    {{-- Footer Section --}}
    <div class="border-t px-6 py-4 text-sm text-gray-650">
        Total halaman: {{ $totalHalaman ?? 'N/A' }}
    </div>
</div>

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JSONParseResource\Pages;
use App\Models\JSONParse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Actions; 
use Filament\Resources\Resource;

class JSONParseResource extends Resource
{
    protected static ?string $model = JSONParse::class;

    protected static ?string $navigationGroup = 'Parsing Documents';
    protected static ?string $modelLabel = 'Upload Documents';
    protected static ?string $pluralModelLabel = 'Parsing Documents';
    protected static ?string $navigationLabel = 'Upload Documents';
    protected static ?string $navigationIcon  = 'heroicon-o-arrow-up-on-square';
    protected static ?string $slug = 'upload-documents';

    // biar URL rapi dan klik menu langsung ke create
    public static function getNavigationUrl(): string
    {
        return static::getUrl('create');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Upload & Preview')
                ->schema([
                    Forms\Components\FileUpload::make('document')
                        ->label('Upload Dokumen')
                        ->disk('local')               // simpan di storage/app
                        ->directory('uploads')        // storage/app/uploads
                        ->preserveFilenames()
                        ->helperText('⚠️ Hanya teks yang bisa diproses. File scan/gambar tidak terbaca.')
                        ->required(),
                    // Dua tombol aksi
                    Actions::make([
                        Actions\Action::make('parse')
                            ->label('Parse Dokumen')
                            ->color('warning')
                            ->action('previewParse'),

                        Actions\Action::make('preview')
                            ->label('Preview Parsing')
                            ->color('info')
                            ->modalHeading('Preview Dokumen')
                            ->modalContent(fn ($livewire) => view('filament.preview-jsonparse', [
                                'content' => $livewire->preview ?? '[Belum ada hasil parsing, silakan parse dulu]',
                                'totalHalaman' => $livewire->totalHalaman ?? null,
                            ]))
                            ->modalWidth('7xl')
                            ->slideOver()
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup'),
                    ])->columnSpanFull(),
                ])
                ->columns(1),

            Forms\Components\Section::make('Metadata')
                ->schema([
                    Forms\Components\Select::make('source_type')
                        ->label('Sumber Data')
                        ->options([
                            'publikasi' => 'Publikasi BPS',
                            'paper_penelitian' => 'Paper Penelitian',
                            'data' => 'Data Statistik',
                        ]),

                    Forms\Components\TextInput::make('title')
                        ->label('Judul'),
                        
                    Forms\Components\TextInput::make('authors')
                        ->label('Penulis/Instansi'),

                    Forms\Components\TextInput::make('year')
                        ->numeric()
                        ->label('Tahun'),

                    Forms\Components\TextInput::make('doi')
                        ->label('DOI (opsional)'),

                    Forms\Components\TagsInput::make('tags')
                        ->label('Kata Kunci'),
                ])
                ->columns(2),
            ]);
    }

    // upload-only: tidak ada list/edit di resource ini
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListJSONParses::route('/'),     
            'create' => Pages\CreateJSONParse::route('/create'),
        ];
    }
}

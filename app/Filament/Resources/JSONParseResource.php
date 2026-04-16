<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TagsInput;
use App\Filament\Resources\JSONParseResource\Pages\ListJSONParses;
use App\Filament\Resources\JSONParseResource\Pages\CreateJSONParse;
use App\Filament\Resources\JSONParseResource\Pages;
use App\Models\JSONParse;
use Filament\Forms; 
use Filament\Resources\Resource;

class JSONParseResource extends Resource
{
    protected static ?string $model = JSONParse::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Parsing Documents';
    protected static ?string $modelLabel = 'Upload Documents';
    protected static ?string $pluralModelLabel = 'Parsing Documents';
    protected static ?string $navigationLabel = 'Upload Documents';
    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-arrow-up-on-square';
    protected static ?string $slug = 'upload-documents';

    // biar URL rapi dan klik menu langsung ke create
    public static function getNavigationUrl(): string
    {
        return static::getUrl('create');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Upload & Preview')
                ->schema([
                    FileUpload::make('document')
                        ->label('Upload Dokumen')
                        ->disk('local')               // simpan di storage/app
                        ->directory('uploads')        // storage/app/uploads
                        ->preserveFilenames()
                        ->helperText('⚠️ Hanya teks yang bisa diproses. File scan/gambar tidak terbaca.')
                        ->required(),
                    // Dua tombol aksi
                    Actions::make([
                        Action::make('parse')
                            ->label('Parse Dokumen')
                            ->color('warning')
                            ->action('previewParse'),

                        Action::make('preview')
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

            Section::make('Metadata')
                ->schema([
                    Select::make('source_type')
                        ->label('Sumber Data')
                        ->options([
                            'publikasi' => 'Publikasi BPS',
                            'paper_penelitian' => 'Paper Penelitian',
                            'data' => 'Data Statistik',
                        ]),

                    TextInput::make('title')
                        ->label('Judul'),
                        
                    TextInput::make('authors')
                        ->label('Penulis/Instansi'),

                    TextInput::make('year')
                        ->numeric()
                        ->label('Tahun'),

                    TextInput::make('doi')
                        ->label('DOI (opsional)'),

                    TagsInput::make('tags')
                        ->label('Kata Kunci'),
                ])
                ->columns(2),
            ]);
    }

    // upload-only: tidak ada list/edit di resource ini
    public static function getPages(): array
    {
        return [
            'index'  => ListJSONParses::route('/'),     
            'create' => CreateJSONParse::route('/create'),
        ];
    }
}

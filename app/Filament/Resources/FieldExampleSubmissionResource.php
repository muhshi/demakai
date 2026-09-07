<?php

namespace App\Filament\Resources;

use Filament\Actions\BulkAction;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\FieldExampleSubmissionResource\Pages\ListFieldExampleSubmissions;
use App\Filament\Resources\FieldExampleSubmissionResource\Pages\CreateFieldExampleSubmission;
use App\Filament\Resources\FieldExampleSubmissionResource\Pages\EditFieldExampleSubmission;
use App\Filament\Resources\FieldExampleSubmissionResource\Pages;
use App\Filament\Resources\FieldExampleSubmissionResource\RelationManagers;
use App\Models\FieldExampleSubmission;
use App\Models\PgKBLI2025;
use App\Models\PgKBLI2020;
use App\Models\PgKBJI2014;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;

class FieldExampleSubmissionResource extends Resource
{
    protected static ?string $model = FieldExampleSubmission::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-plus-circle';
    protected static ?string $navigationLabel = 'Pengajuan Contoh Lapangan';
    protected static ?string $modelLabel = 'Pengajuan Contoh Lapangan';
    protected static ?string $pluralModelLabel = 'Pengajuan Contoh Lapangan';

    public static function resolveModel(?string $type): ?string
    {
        if (!$type) return PgKBLI2025::class;
        $typeUpper = strtoupper(trim($type));
        if (str_contains($typeUpper, 'KBJI')) return PgKBJI2014::class;
        if (str_contains($typeUpper, '2020')) return PgKBLI2020::class;
        return PgKBLI2025::class;
    }

    public static function getTitleForCode(?string $type, ?string $kode): ?string
    {
        if (!$kode) return null;
        $model = static::resolveModel($type);
        if (!$model) return null;

        $item = $model::where('kode', trim($kode))->first();
        return $item?->judul;
    }

    public static function checkAlreadyAcc(?string $type, ?string $kode, ?string $content, ?int $excludeId = null): ?string
    {
        $cleanContent = mb_strtolower(trim((string)$content));
        if (!$cleanContent || !$kode) return null;

        $kode = trim($kode);
        $model = static::resolveModel($type);

        // 1. Cek di master tabel (contoh_lapangan)
        if ($model) {
            $entry = $model::where('kode', $kode)->first();
            if ($entry && !empty($entry->contoh_lapangan)) {
                $arr = is_array($entry->contoh_lapangan) ? $entry->contoh_lapangan : (json_decode($entry->contoh_lapangan, true) ?: []);
                foreach ($arr as $ex) {
                    if (mb_strtolower(trim($ex)) === $cleanContent) {
                        $mName = class_basename($model);
                        return "Sudah terdaftar di master {$mName} (Kode {$kode}: '{$entry->judul}')";
                    }
                }
            }
        }

        // 2. Cek di pengajuan yang sudah disetujui (approved)
        $q = FieldExampleSubmission::where('status', 'approved')
            ->where('kode', $kode)
            ->whereRaw('LOWER(TRIM(content)) = ?', [$cleanContent]);
        if ($excludeId) {
            $q->where('id', '!=', $excludeId);
        }
        $approved = $q->first();
        if ($approved) {
            return "Sudah pernah disetujui (ACC) pada pengajuan ID #{$approved->id} untuk kode {$kode}";
        }

        return null;
    }

    public static function searchClassifications(?string $type, string $search = ''): array
    {
        $model = static::resolveModel($type);
        if (!$model) {
            return [];
        }

        $cleanSearch = trim($search);
        $query = $model::query();

        if ($cleanSearch !== '') {
            $query->where(function ($q) use ($cleanSearch) {
                $q->where('kode', 'ILIKE', "{$cleanSearch}%")
                  ->orWhere('judul', 'ILIKE', "%{$cleanSearch}%");
            });
        }

        return $query->orderBy('kode')
            ->limit(30)
            ->get(['kode', 'judul'])
            ->mapWithKeys(fn ($item) => [$item->kode => "{$item->kode} - {$item->judul}"])
            ->toArray();
    }

    public static function getClassificationLabel(?string $type, ?string $value): ?string
    {
        if (!$value) return null;
        $title = static::getTitleForCode($type, $value);
        return $title ? "{$value} - {$title}" : $value;
    }

    public static function getFormComponents(bool $includeStatus = true): array
    {
        $components = [
            Select::make('type')
                ->label('Jenis Klasifikasi')
                ->options([
                    'KBLI 2025' => 'KBLI 2025',
                    'KBLI 2020' => 'KBLI 2020',
                    'KBJI 2014' => 'KBJI 2014',
                ])
                ->required()
                ->live(),

            Hidden::make('kode')
                ->required(),

            Radio::make('input_mode')
                ->label('Cara Memilih/Mengubah Kode')
                ->options([
                    'dropdown' => 'Pilihan 1: Cari dari Dropdown List KBLI / KBJI',
                    'manual' => 'Pilihan 2: Ketik Kode Manual',
                ])
                ->default('dropdown')
                ->inline()
                ->live()
                ->dehydrated(false),

            Select::make('kode_select')
                ->label('Pilih Kode KBLI / KBJI')
                ->placeholder('Ketik kata kunci atau kode untuk mencari...')
                ->searchable()
                ->getSearchResultsUsing(fn (string $search, $get) => static::searchClassifications($get('type'), $search))
                ->getOptionLabelUsing(fn ($value, $get) => static::getClassificationLabel($get('type'), $value))
                ->afterStateHydrated(function ($set, $record, $get) {
                    $k = $record?->kode ?? $get('kode');
                    if ($k) {
                        $set('kode_select', $k);
                    }
                })
                ->afterStateUpdated(function ($state, $set) {
                    $set('kode', $state);
                    $set('kode_input', $state);
                })
                ->live()
                ->dehydrated(false)
                ->helperText(function ($get) {
                    $kode = $get('kode');
                    $type = $get('type');
                    if (!$kode) return null;
                    $title = static::getTitleForCode($type, $kode);
                    return $title ? "📖 Terpilih: {$kode} - {$title}" : null;
                })
                ->visible(fn ($get) => ($get('input_mode') ?? 'dropdown') === 'dropdown'),

            TextInput::make('kode_input')
                ->label('Ketik Kode KBLI / KBJI')
                ->placeholder('Contoh: 01111')
                ->maxLength(10)
                ->live(debounce: 300)
                ->afterStateHydrated(function ($set, $record, $get) {
                    $k = $record?->kode ?? $get('kode');
                    if ($k) {
                        $set('kode_input', $k);
                    }
                })
                ->afterStateUpdated(function ($state, $set) {
                    $set('kode', $state);
                    $set('kode_select', $state);
                })
                ->dehydrated(false)
                ->helperText(function ($get) {
                    $kode = $get('kode_input') ?: $get('kode');
                    $type = $get('type');
                    if (!$kode) return 'Ketik kode di atas untuk melihat judul klasifikasi secara langsung.';
                    $title = static::getTitleForCode($type, $kode);
                    return $title ? "📖 {$kode} - {$title}" : '⚠️ Kode tidak ditemukan dalam database master';
                })
                ->visible(fn ($get) => $get('input_mode') === 'manual'),

            Placeholder::make('acc_warning')
                ->label('')
                ->hidden(fn ($get) => !static::checkAlreadyAcc($get('type'), $get('kode'), $get('content')))
                ->content(function ($get) {
                    $msg = static::checkAlreadyAcc($get('type'), $get('kode'), $get('content'));
                    return new HtmlString('
                        <div style="padding: 12px 16px; background-color: #fffbeb; border: 1px solid #fde68a; border-left: 4px solid #f59e0b; border-radius: 6px; color: #92400e; font-size: 0.875rem;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 1.25rem;">⚠️</span>
                                <div>
                                    <strong style="font-weight: 600;">Peringatan: Contoh Lapangan Ini Sudah Pernah di-ACC!</strong>
                                    <div style="margin-top: 2px;">' . e($msg) . '</div>
                                </div>
                            </div>
                        </div>
                    ');
                })
                ->columnSpanFull(),

            Textarea::make('content')
                ->label('Isi Contoh Lapangan')
                ->required()
                ->rows(3)
                ->live(debounce: 300)
                ->columnSpanFull(),
        ];

        if ($includeStatus) {
            $components[] = Select::make('status')
                ->label('Status')
                ->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ])
                ->required();
        }

        return $components;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(static::getFormComponents(true));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('type')
                    ->searchable()
                    ->badge(),
                TextColumn::make('kode')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('primary')
                    ->tooltip(fn(FieldExampleSubmission $record) => static::getTitleForCode($record->type, $record->kode)),
                TextColumn::make('content')
                    ->wrap()
                    ->limit(100)
                    ->description(function (FieldExampleSubmission $record) {
                        $warn = static::checkAlreadyAcc($record->type, $record->kode, $record->content, $record->status === 'approved' ? $record->id : null);
                        return $warn ? "⚠️ {$warn}" : null;
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis KBLI/KBJI')
                    ->options([
                        'KBLI 2025' => 'KBLI 2025',
                        'KBLI 2020' => 'KBLI 2020',
                        'KBJI 2014' => 'KBJI 2014',
                    ]),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn(FieldExampleSubmission $record) => $record->status === 'pending')
                    ->modalDescription(function (FieldExampleSubmission $record) {
                        $warn = static::checkAlreadyAcc($record->type, $record->kode, $record->content);
                        if ($warn) {
                            return "⚠️ PERHATIAN: {$warn}. Apakah Anda yakin tetap ingin menyetujui pengajuan ini?";
                        }
                        return 'Apakah Anda yakin ingin menyetujui pengajuan contoh lapangan ini? Contoh lapangan akan otomatis ditambahkan ke database master.';
                    })
                    ->action(function (FieldExampleSubmission $record) {
                        $model = static::resolveModel($record->type);

                        if ($model) {
                            $entry = $model::where('kode', $record->kode)->first();
                            if ($entry) {
                                $currentExamples = is_array($entry->contoh_lapangan) ? $entry->contoh_lapangan : [];
                                // Split new content by comma and trim each part
                                $newExamples = array_map('trim', explode(',', $record->content));
                                // Merge and remove duplicates
                                $updatedExamples = array_unique(array_merge($currentExamples, $newExamples));

                                $entry->update(['contoh_lapangan' => array_values($updatedExamples)]);
                            }
                        }

                        $record->update(['status' => 'approved']);
                    })
                    ->requiresConfirmation(),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn(FieldExampleSubmission $record) => $record->status === 'pending')
                    ->action(fn(FieldExampleSubmission $record) => $record->update(['status' => 'rejected']))
                    ->requiresConfirmation(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('bulkApprove')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            foreach ($records as $record) {
                                if ($record->status !== 'pending') {
                                    continue;
                                }
                                $model = match ($record->type) {
                                    'KBLI 2025' => \App\Models\PgKBLI2025::class,
                                    'KBLI 2020' => \App\Models\PgKBLI2020::class,
                                    'KBJI 2014' => \App\Models\PgKBJI2014::class,
                                    default => null,
                                };

                                if ($model) {
                                    $entry = $model::where('kode', $record->kode)->first();
                                    if ($entry) {
                                        $currentExamples = is_array($entry->contoh_lapangan) ? $entry->contoh_lapangan : [];
                                        $newExamples = array_map('trim', explode(',', $record->content));
                                        $updatedExamples = array_unique(array_merge($currentExamples, $newExamples));

                                        $entry->update(['contoh_lapangan' => array_values($updatedExamples)]);
                                    }
                                }

                                $record->update(['status' => 'approved']);
                            }
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('bulkReject')
                        ->label('Reject Selected')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->update(['status' => 'rejected']);
                                    $count++;
                                }
                            }
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Pengajuan Ditolak')
                                ->body("{$count} pengajuan berhasil diubah statusnya menjadi rejected.")
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFieldExampleSubmissions::route('/'),
            'create' => CreateFieldExampleSubmission::route('/create'),
            'edit' => EditFieldExampleSubmission::route('/{record}/edit'),
        ];
    }
}

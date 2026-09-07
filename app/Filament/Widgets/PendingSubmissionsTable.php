<?php

namespace App\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\FieldExampleSubmission;
use App\Models\PgKBLI2025;
use App\Models\PgKBLI2020;
use App\Models\PgKBJI2014;
use Filament\Tables\Columns\TextColumn;


use Filament\Actions\EditAction;
use App\Filament\Resources\FieldExampleSubmissionResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;

class PendingSubmissionsTable extends BaseWidget
{
    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FieldExampleSubmission::query()->where('status', 'pending')->latest()
            )
            ->heading('Pengajuan Menunggu Persetujuan')
            ->columns([
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('kode')
                    ->weight('bold')
                    ->description(fn(FieldExampleSubmission $record) => FieldExampleSubmissionResource::getTitleForCode($record->type, $record->kode)),
                TextColumn::make('content')
                    ->wrap()
                    ->limit(80)
                    ->description(function (FieldExampleSubmission $record) {
                        $warn = FieldExampleSubmissionResource::checkAlreadyAcc($record->type, $record->kode, $record->content);
                        return $warn ? "⚠️ {$warn}" : null;
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->modalDescription(function (FieldExampleSubmission $record) {
                        $warn = FieldExampleSubmissionResource::checkAlreadyAcc($record->type, $record->kode, $record->content);
                        if ($warn) {
                            return "⚠️ PERHATIAN: {$warn}. Apakah Anda yakin tetap ingin menyetujui pengajuan ini?";
                        }
                        return 'Apakah Anda yakin ingin menyetujui pengajuan contoh lapangan ini? Contoh lapangan akan otomatis ditambahkan ke database master.';
                    })
                    ->action(function (FieldExampleSubmission $record) {
                        $model = FieldExampleSubmissionResource::resolveModel($record->type);

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
                    })
                    ->requiresConfirmation(),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(fn(FieldExampleSubmission $record) => $record->update(['status' => 'rejected']))
                    ->requiresConfirmation(),
                EditAction::make()
                    ->form([
                        Select::make('type')
                            ->label('Jenis Klasifikasi')
                            ->options([
                                'KBLI 2025' => 'KBLI 2025',
                                'KBLI 2020' => 'KBLI 2020',
                                'KBJI 2014' => 'KBJI 2014',
                            ])
                            ->required()
                            ->live(),
                        TextInput::make('kode')
                            ->label('Kode KBLI / KBJI')
                            ->required()
                            ->maxLength(10)
                            ->live(onBlur: true)
                            ->helperText(function ($get) {
                                $kode = $get('kode');
                                $type = $get('type');
                                if (!$kode) return null;
                                $title = FieldExampleSubmissionResource::getTitleForCode($type, $kode);
                                return $title ? "📖 {$title}" : '⚠️ Kode tidak ditemukan dalam database master';
                            }),
                        Placeholder::make('acc_warning')
                            ->label('')
                            ->hidden(fn ($get) => !FieldExampleSubmissionResource::checkAlreadyAcc($get('type'), $get('kode'), $get('content')))
                            ->content(function ($get) {
                                $msg = FieldExampleSubmissionResource::checkAlreadyAcc($get('type'), $get('kode'), $get('content'));
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
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                    ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
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
                                    'KBLI 2025' => PgKBLI2025::class,
                                    'KBLI 2020' => PgKBLI2020::class,
                                    'KBJI 2014' => PgKBJI2014::class,
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
}

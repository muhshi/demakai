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


class PendingSubmissionsTable extends BaseWidget
{
    protected static ?int $sort = 2;
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
                    ->weight('bold'),
                TextColumn::make('content')
                    ->limit(50),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function (FieldExampleSubmission $record) {
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
                    })
                    ->requiresConfirmation(),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(fn(FieldExampleSubmission $record) => $record->update(['status' => 'rejected']))
                    ->requiresConfirmation(),
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
                ]),
            ]);
    }
}

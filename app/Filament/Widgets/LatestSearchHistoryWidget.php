<?php

namespace App\Filament\Widgets;

use App\Models\SearchHistory;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestSearchHistoryWidget extends BaseWidget
{
    protected static ?string $heading = 'Riwayat Pencarian Terkini';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SearchHistory::query()->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('query')
                    ->label('Kata Kunci')
                    ->searchable(),
                Tables\Columns\TextColumn::make('results_count')
                    ->label('Hasil')
                    ->badge()
                    ->color(fn(int $state): string => match (true) {
                        $state === 0 => 'danger',
                        $state < 5 => 'warning',
                        default => 'success',
                    }),
                Tables\Columns\TextColumn::make('detected_type')
                    ->label('Tipe')
                    ->badge(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->paginated([5, 10]);
    }
}

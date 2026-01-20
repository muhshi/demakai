<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FieldExampleSubmissionResource\Pages;
use App\Filament\Resources\FieldExampleSubmissionResource\RelationManagers;
use App\Models\FieldExampleSubmission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FieldExampleSubmissionResource extends Resource
{
    protected static ?string $model = FieldExampleSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';
    protected static ?string $navigationLabel = 'Pengajuan Contoh Lapangan';
    protected static ?string $modelLabel = 'Pengajuan Contoh Lapangan';
    protected static ?string $pluralModelLabel = 'Pengajuan Contoh Lapangan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('type')
                    ->readOnly(),
                Forms\Components\TextInput::make('kode')
                    ->readOnly(),
                Forms\Components\Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('status')
                    ->readOnly(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->searchable()
                    ->badge(),
                Tables\Columns\TextColumn::make('kode')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('content')
                    ->limit(50),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn(FieldExampleSubmission $record) => $record->status === 'pending')
                    ->action(function (FieldExampleSubmission $record) {
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
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn(FieldExampleSubmission $record) => $record->status === 'pending')
                    ->action(fn(FieldExampleSubmission $record) => $record->update(['status' => 'rejected']))
                    ->requiresConfirmation(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListFieldExampleSubmissions::route('/'),
            'create' => Pages\CreateFieldExampleSubmission::route('/create'),
            'edit' => Pages\EditFieldExampleSubmission::route('/{record}/edit'),
        ];
    }
}

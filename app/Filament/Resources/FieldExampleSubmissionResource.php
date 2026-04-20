<?php

namespace App\Filament\Resources;

use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
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

class FieldExampleSubmissionResource extends Resource
{
    protected static ?string $model = FieldExampleSubmission::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-plus-circle';
    protected static ?string $navigationLabel = 'Pengajuan Contoh Lapangan';
    protected static ?string $modelLabel = 'Pengajuan Contoh Lapangan';
    protected static ?string $pluralModelLabel = 'Pengajuan Contoh Lapangan';

    public static function form(Form $form): Form
    {
        return $form->schema([
                TextInput::make('type')
                    ->readOnly(),
                TextInput::make('kode')
                    ->readOnly(),
                Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->readOnly(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->searchable()
                    ->badge(),
                TextColumn::make('kode')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('content')
                    ->limit(50),
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
                //
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn(FieldExampleSubmission $record) => $record->status === 'pending')
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

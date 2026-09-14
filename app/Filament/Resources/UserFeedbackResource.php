<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserFeedbackResource\Pages\ListUserFeedbacks;
use App\Filament\Resources\UserFeedbackResource\Pages\ViewUserFeedback;
use App\Models\UserFeedback;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class UserFeedbackResource extends Resource
{
    protected static ?string $model = UserFeedback::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Saran & Masukan';
    protected static ?string $modelLabel = 'Saran & Masukan';
    protected static ?string $pluralModelLabel = 'Saran & Masukan Pengguna';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'baru')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'saran_fitur' => 'primary',
                        'koreksi_hasil' => 'warning',
                        'kendala_bug' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => UserFeedback::getCategoryOptions()[$state] ?? $state),

                TextColumn::make('message')
                    ->label('Pesan Masukan')
                    ->searchable()
                    ->limit(65)
                    ->wrap(),

                TextColumn::make('search_query')
                    ->label('Kueri Terkait')
                    ->placeholder('-')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                TextColumn::make('sender_name')
                    ->label('Pengirim')
                    ->placeholder('Anonim')
                    ->searchable(),

                TextColumn::make('sender_contact')
                    ->label('Kontak')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'baru' => 'warning',
                        'ditinjau' => 'info',
                        'diterapkan' => 'success',
                        'diarsipkan' => 'gray',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn(string $state): string => UserFeedback::getStatusOptions()[$state] ?? $state),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Masukan')
                    ->options(UserFeedback::getStatusOptions()),

                SelectFilter::make('category')
                    ->label('Kategori')
                    ->options(UserFeedback::getCategoryOptions()),
            ])
            ->actions([
                ViewAction::make()
                    ->modalHeading('Detail Saran & Masukan Pengguna')
                    ->schema([
                        Placeholder::make('waktu')
                            ->label('Waktu Pengiriman')
                            ->content(fn(?UserFeedback $record) => $record?->created_at?->translatedFormat('l, d F Y H:i:s') ?? '-'),

                        Placeholder::make('kategori_display')
                            ->label('Kategori')
                            ->content(fn(?UserFeedback $record) => UserFeedback::getCategoryOptions()[$record?->category] ?? '-'),

                        Placeholder::make('search_query_display')
                            ->label('Kueri Pencarian Terkait (Saat Masukan Dikirim)')
                            ->content(fn(?UserFeedback $record) => $record?->search_query ?: '(Tidak ada kueri aktif)'),

                        Placeholder::make('pengirim_info')
                            ->label('Nama & Kontak Pengirim')
                            ->content(fn(?UserFeedback $record) => ($record?->sender_name ?: 'Anonim') . ($record?->sender_contact ? " ({$record->sender_contact})" : '')),

                        Placeholder::make('pesan_lengkap')
                            ->label('Isi Pesan Masukan')
                            ->content(fn(?UserFeedback $record) => new HtmlString('<div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 whitespace-pre-line text-sm font-sans leading-relaxed text-slate-800 dark:text-slate-200">' . e($record?->message) . '</div>')),

                        Select::make('status')
                            ->label('Status Tindak Lanjut')
                            ->options(UserFeedback::getStatusOptions())
                            ->required(),

                        Textarea::make('admin_notes')
                            ->label('Catatan Tim Pengembang (Internal)')
                            ->placeholder('Tuliskan catatan tindak lanjut atau rencana penerapan fitur di sini...')
                            ->rows(3),
                    ]),

                Action::make('markReviewed')
                    ->label('Tandai Ditinjau')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn(UserFeedback $record) => $record->status === 'baru')
                    ->action(function (UserFeedback $record) {
                        $record->update(['status' => 'ditinjau']);
                        Notification::make()
                            ->success()
                            ->title('Status Diperbarui')
                            ->body('Masukan telah ditandai sedang ditinjau.')
                            ->send();
                    }),

                Action::make('markApplied')
                    ->label('Tandai Diterapkan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(UserFeedback $record) => in_array($record->status, ['baru', 'ditinjau']))
                    ->action(function (UserFeedback $record) {
                        $record->update(['status' => 'diterapkan']);
                        Notification::make()
                            ->success()
                            ->title('Status Diperbarui')
                            ->body('Masukan telah ditandai sudah diterapkan!')
                            ->send();
                    }),

                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserFeedbacks::route('/'),
        ];
    }
}

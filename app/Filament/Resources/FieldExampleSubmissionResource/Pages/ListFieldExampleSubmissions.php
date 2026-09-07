<?php

namespace App\Filament\Resources\FieldExampleSubmissionResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use App\Filament\Resources\FieldExampleSubmissionResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use App\Models\FieldExampleSubmission;

class ListFieldExampleSubmissions extends ListRecords
{
    protected static string $resource = FieldExampleSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('rejectDuplicates')
                ->label('Tolak Pengajuan Duplikat')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Tolak Semua Pengajuan Duplikat?')
                ->modalDescription('Sistem akan menolak pengajuan pending yang memiliki Jenis, Kode, dan Isi yang sama. Entri pertama tetap pending, sedangkan entri duplikat lainnya akan diubah statusnya menjadi rejected.')
                ->modalSubmitActionLabel('Ya, Tolak Duplikat')
                ->action(function () {
                    $approvedSubmissions = FieldExampleSubmission::where('status', 'approved')->get();
                    $approvedKeys = [];
                    foreach ($approvedSubmissions as $app) {
                        $key = trim($app->type) . '|' . trim($app->kode) . '|' . trim(mb_strtolower($app->content));
                        $approvedKeys[$key] = true;
                    }

                    $pending = FieldExampleSubmission::where('status', 'pending')
                        ->orderBy('id', 'asc')
                        ->get();

                    $seen = [];
                    $idsToReject = [];

                    foreach ($pending as $item) {
                        $key = trim($item->type) . '|' . trim($item->kode) . '|' . trim(mb_strtolower($item->content));
                        if (isset($approvedKeys[$key]) || isset($seen[$key])) {
                            $idsToReject[] = $item->id;
                        } else {
                            $seen[$key] = true;
                        }
                    }

                    if (empty($idsToReject)) {
                        Notification::make()
                            ->info()
                            ->title('Tidak Ada Duplikat')
                            ->body('Tidak ditemukan pengajuan pending yang duplikat.')
                            ->send();
                        return;
                    }

                    FieldExampleSubmission::whereIn('id', $idsToReject)
                        ->update(['status' => 'rejected']);

                    Notification::make()
                        ->success()
                        ->title('Duplikat Berhasil Ditolak')
                        ->body(count($idsToReject) . ' pengajuan duplikat berhasil ditolak.')
                        ->send();
                }),
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'Semua' => Tab::make()
                ->badge(FieldExampleSubmission::count()),
            'Belum Disetujui' => Tab::make()
                ->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query) => $query->where('status', 'pending'))
                ->badge(FieldExampleSubmission::where('status', 'pending')->count())
                ->badgeColor('warning'),
            'Sudah Disetujui' => Tab::make()
                ->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query) => $query->where('status', 'approved'))
                ->badge(FieldExampleSubmission::where('status', 'approved')->count())
                ->badgeColor('success'),
            'Ditolak' => Tab::make()
                ->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query) => $query->where('status', 'rejected'))
                ->badge(FieldExampleSubmission::where('status', 'rejected')->count())
                ->badgeColor('danger'),
        ];
    }
}

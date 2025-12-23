<?php

namespace App\Filament\Resources\AbsensiResource\Pages;

use App\Filament\Resources\AbsensiResource;
use App\Models\Kelas;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;

class ListAbsensis extends ListRecords
{
    use InteractsWithForms;

    protected static string $resource = AbsensiResource::class;
    protected static string $view = 'filament.resources.absensi-resource.pages.list-absensis';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'tanggal' => Carbon::now()->format('Y-m-d'),
            'kelas_id' => null,
            'status' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Absensi')
                    ->description('Pilih tanggal, kelas, dan status untuk memfilter data absensi')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                DatePicker::make('tanggal')
                                    ->label('Tanggal')
                                    ->required()
                                    ->default(Carbon::now())
                                    ->live()
                                    ->afterStateUpdated(function ($state) {
                                        session(['absensi_filter_tanggal' => $state]);
                                        $this->resetTable();
                                    }),
                                
                                Select::make('kelas_id')
                                    ->label('Kelas*')
                                    ->options(fn () => ['' => 'Semua Kelas'] + Kelas::query()->orderBy('nama_kelas')->pluck('nama_kelas', 'id')->all())
                                    ->searchable()
                                    ->placeholder('Semua Kelas')
                                    ->default('')
                                    ->live()
                                    ->afterStateUpdated(function ($state) {
                                        session(['absensi_filter_kelas_id' => $state]);
                                        $this->resetTable();
                                    }),
                                
                                Select::make('status')
                                    ->label('Status*')
                                    ->options([
                                        'hadir' => 'Hadir',
                                        'izin' => 'Izin',
                                        'sakit' => 'Sakit',
                                        'alpa' => 'Alpa',
                                        'libur' => 'Libur',
                                    ])
                                    ->placeholder('Semua Status')
                                    ->live()
                                    ->afterStateUpdated(function ($state) {
                                        session(['absensi_filter_status' => $state]);
                                        $this->resetTable();
                                    }),
                            ]),
                    ])
                    ->collapsible(),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100];
    }
}

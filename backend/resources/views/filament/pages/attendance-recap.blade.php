<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <div>
                    <label for="recap-month" class="block text-xs font-semibold text-slate-500 dark:text-gray-400">Bulan</label>
                    <select id="recap-month" wire:model.live="month" class="mt-1 rounded-xl border-slate-300 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        @foreach($this->getMonthOptions() as $monthNumber => $monthLabel)
                            <option value="{{ $monthNumber }}">{{ $monthLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="recap-year" class="block text-xs font-semibold text-slate-500 dark:text-gray-400">Tahun</label>
                    <select id="recap-year" wire:model.live="year" class="mt-1 rounded-xl border-slate-300 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        @for($yearOption = now()->year - 2; $yearOption <= now()->year + 2; $yearOption++)
                            <option value="{{ $yearOption }}">{{ $yearOption }}</option>
                        @endfor
                    </select>
                </div>
                <span class="text-sm font-semibold text-slate-600 dark:text-gray-300">{{ $periodLabel }}</span>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    wire:click="exportExcel"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-emerald-500"
                >
                    <x-heroicon-m-document-arrow-down class="h-4 w-4" />
                    Ekspor Excel
                </button>
                <button
                    type="button"
                    wire:click="exportPdf"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    <x-heroicon-m-document-text class="h-4 w-4 text-rose-500" />
                    Ekspor PDF
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <div class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-gray-400">Total laporan</div>
                <div class="mt-2 text-3xl font-black text-slate-900 dark:text-white">{{ $totalSubmissions }}</div>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-900 dark:bg-emerald-950/40">
                <div class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Guru masuk</div>
                <div class="mt-2 text-3xl font-black text-emerald-700 dark:text-emerald-300">{{ $teacherPresentCount }}</div>
            </div>
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5 dark:border-rose-900 dark:bg-rose-950/40">
                <div class="text-xs font-bold uppercase tracking-wide text-rose-700 dark:text-rose-300">Guru tidak masuk</div>
                <div class="mt-2 text-3xl font-black text-rose-700 dark:text-rose-300">{{ $teacherAbsentCount }}</div>
            </div>
        </div>

        <section class="rounded-2xl border border-rose-200 bg-white p-5 dark:border-rose-900 dark:bg-gray-800">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Guru perlu perhatian</h2>
                    <p class="text-sm text-slate-500 dark:text-gray-400">
                        Guru dengan penilaian Kurang pada {{ $monthName }}.
                    </p>
                </div>
                <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                    {{ $totalLowRatings }} nilai Kurang
                </span>
            </div>

            @if($attentionTeachers->isNotEmpty())
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($attentionTeachers as $teacher)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 dark:border-rose-900 dark:bg-rose-950/40">
                            <span class="font-bold text-slate-800 dark:text-gray-100">{{ $teacher['teacher'] }}</span>
                            <span class="whitespace-nowrap rounded-lg bg-rose-600 px-2.5 py-1 text-xs font-bold text-white">
                                {{ $teacher['kurang_count'] }} Kurang
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                    Tidak ada guru dengan nilai Kurang pada periode ini.
                </p>
            @endif
        </section>

        <section class="overflow-x-auto rounded-2xl border border-slate-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="p-5">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Rekap Nilai Guru</h2>
                <p class="text-sm text-slate-500 dark:text-gray-400">Jumlah penilaian setiap guru pada periode terpilih.</p>
            </div>
            <table class="recap-table min-w-full">
                <thead>
                    <tr>
                        <th>Nama Guru</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Sangat baik</th>
                        <th class="text-center">Baik</th>
                        <th class="text-center">Cukup</th>
                        <th class="text-center">Kurang</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teacherSummaries as $teacher)
                        <tr>
                            <td class="font-bold text-slate-900 dark:text-white">{{ $teacher['teacher'] }}</td>
                            <td class="text-center font-semibold">{{ $teacher['total'] }}</td>
                            @foreach(\App\Models\TeacherEvaluation::RATINGS as $ratingOption)
                                <td @class([
                                    'text-center font-bold',
                                    'text-rose-600 dark:text-rose-400' => $ratingOption['value'] === 'kurang' && $teacher['rating_counts'][$ratingOption['value']] > 0,
                                ])>
                                    {{ $teacher['rating_counts'][$ratingOption['value']] }}
                                </td>
                            @endforeach
                            <td>
                                @if($teacher['kurang_count'] > 0)
                                    <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                                        Perlu perhatian
                                    </span>
                                @else
                                    <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">Tidak ada nilai kurang</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-sm text-slate-500 dark:text-gray-400">
                                Belum ada penilaian guru pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Rekap Kehadiran Guru</h2>
                <p class="text-sm text-slate-500 dark:text-gray-400">Kehadiran, penilaian, dan catatan pengguna dalam satu daftar.</p>
            </div>
            <div class="relative w-full sm:w-80">
                <x-heroicon-m-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                    type="search"
                    wire:model.live.debounce.250ms="search"
                    placeholder="Cari guru, kelas, mapel, atau catatan..."
                    class="w-full rounded-xl border-slate-300 py-2 pl-9 pr-3 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                />
            </div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <table class="recap-table min-w-full">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Kelas / Jurusan</th>
                        <th>Jam / Mapel</th>
                        <th>Guru</th>
                        <th class="text-center">Kehadiran</th>
                        <th class="text-center">Penilaian</th>
                        <th>Diisi oleh</th>
                        <th class="text-center">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td class="whitespace-nowrap text-xs font-semibold text-slate-600 dark:text-gray-300">
                                {{ \Carbon\Carbon::parse($row['date'])->translatedFormat('d M Y') }}
                            </td>
                            <td>
                                <div class="font-bold text-sm text-slate-900 dark:text-white">{{ $row['class'] }}</div>
                                <div class="text-[11px] text-slate-400">{{ $row['major'] ?: 'Umum' }}</div>
                            </td>
                            <td>
                                <div class="font-semibold text-sm text-slate-700 dark:text-gray-200">{{ $row['period'] }}</div>
                                <div class="text-[11px] text-slate-400">{{ $row['subject'] }}</div>
                            </td>
                            <td class="whitespace-nowrap text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $row['teacher'] }}</td>
                            <td class="text-center">
                                <span @class([
                                    'inline-flex whitespace-nowrap rounded-lg border px-2.5 py-1 text-xs font-bold',
                                    'border-emerald-200 bg-emerald-50 text-emerald-700' => $row['teacher_present'] === true,
                                    'border-rose-200 bg-rose-50 text-rose-700' => $row['teacher_present'] === false,
                                    'border-slate-200 bg-slate-50 text-slate-500' => $row['teacher_present'] === null,
                                ])>
                                    {{ $row['teacher_presence'] }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="inline-flex rounded-lg border border-violet-200 bg-violet-50 px-2.5 py-1 text-xs font-bold text-violet-700">
                                    {{ $row['rating'] }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap text-xs font-semibold text-slate-600 dark:text-gray-300">{{ $row['recorded_by'] }}</td>
                            <td class="text-center">
                                <button
                                    type="button"
                                    wire:click="showDetails({{ $row['id'] }})"
                                    class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-300 dark:hover:bg-indigo-900"
                                >
                                    <x-heroicon-m-eye class="h-4 w-4" />
                                    Lihat
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-12 text-center">
                                <x-heroicon-o-clipboard-document-list class="mx-auto h-10 w-10 text-slate-400" />
                                <p class="mt-3 text-sm font-bold text-slate-700 dark:text-gray-300">
                                    {{ trim($search) !== '' ? 'Tidak ada laporan yang cocok dengan pencarian.' : 'Belum ada laporan pada periode ini.' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-400">Laporan dari form pengguna akan muncul di sini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($selectedEvaluation)
        <div
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/60 p-4"
            wire:click.self="closeDetails"
            wire:keydown.escape.window="closeDetails"
        >
            <section
                role="dialog"
                aria-modal="true"
                aria-labelledby="evaluation-detail-title"
                class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-800"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 id="evaluation-detail-title" class="text-lg font-bold text-slate-900 dark:text-white">Detail laporan guru</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                            {{ $selectedEvaluation->date->translatedFormat('d F Y') }}
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="closeDetails"
                        aria-label="Tutup detail"
                        class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-gray-700"
                    >
                        <x-heroicon-m-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <dl class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold text-slate-500 dark:text-gray-400">Guru</dt>
                        <dd class="mt-1 font-bold text-slate-900 dark:text-white">{{ $selectedEvaluation->teacher?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-500 dark:text-gray-400">Kehadiran</dt>
                        <dd class="mt-1 font-bold text-slate-900 dark:text-white">
                            @if($selectedEvaluation->teacher_present === null)
                                Belum dicatat
                            @elseif($selectedEvaluation->teacher_present)
                                Guru masuk
                            @else
                                Guru tidak masuk
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-500 dark:text-gray-400">Kelas / Jurusan</dt>
                        <dd class="mt-1 font-bold text-slate-900 dark:text-white">
                            {{ $selectedEvaluation->schoolClass?->name ?? '-' }} / {{ $selectedEvaluation->schoolClass?->major ?: 'Umum' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-500 dark:text-gray-400">Jam / Mata pelajaran</dt>
                        <dd class="mt-1 font-bold text-slate-900 dark:text-white">
                            {{ $selectedEvaluation->period?->label ?? '-' }} / {{ $selectedEvaluation->subject?->name ?? '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-500 dark:text-gray-400">Penilaian</dt>
                        <dd class="mt-1 font-bold text-slate-900 dark:text-white">
                            {{ collect(\App\Models\TeacherEvaluation::RATINGS)->firstWhere('value', $selectedEvaluation->rating)['label'] ?? $selectedEvaluation->rating }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-500 dark:text-gray-400">Diisi oleh</dt>
                        <dd class="mt-1 font-bold text-slate-900 dark:text-white">{{ $selectedEvaluation->recordedBy?->name ?? '-' }}</dd>
                    </div>
                </dl>

                <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                    <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-gray-400">Catatan pengguna</h3>
                    <p class="mt-2 whitespace-pre-wrap text-sm text-slate-800 dark:text-gray-200">
                        {{ $selectedEvaluation->note ?: 'Tidak ada catatan.' }}
                    </p>
                </div>

                <div class="mt-5 flex justify-end">
                    <button
                        type="button"
                        wire:click="closeDetails"
                        class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-indigo-500"
                    >
                        Tutup
                    </button>
                </div>
            </section>
        </div>
    @endif
</x-filament-panels::page>

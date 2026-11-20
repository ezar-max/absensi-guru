<x-filament-panels::page>
    <div class="admin-custom space-y-6">
        <!-- Filter Toolbar & Export Buttons -->
        <div class="recap-toolbar">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold shadow-sm">
                        <x-heroicon-m-calendar class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Periode Rekapitulasi</div>
                        <div class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ $monthName }}</div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <select
                        wire:model.live="month"
                        class="rounded-xl border border-slate-300 dark:border-gray-600 bg-slate-50/70 dark:bg-gray-700 text-slate-900 dark:text-gray-100 text-xs font-bold focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 py-2 px-3 shadow-sm transition cursor-pointer"
                    >
                        @foreach($this->getMonthOptions() as $mNum => $mLabel)
                            <option value="{{ $mNum }}">{{ $mLabel }}</option>
                        @endforeach
                    </select>

                    <select
                        wire:model.live="year"
                        class="rounded-xl border border-slate-300 dark:border-gray-600 bg-slate-50/70 dark:bg-gray-700 text-slate-900 dark:text-gray-100 text-xs font-bold focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 py-2 px-3 shadow-sm transition cursor-pointer"
                    >
                        @for($y = now()->year - 2; $y <= now()->year + 2; $y++)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <span class="hidden lg:inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 dark:bg-gray-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-gray-700">
                    {{ $periodLabel }}
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <button
                    type="button"
                    wire:click="exportExcel"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-600/20 transition"
                    title="Unduh laporan Excel (.xlsx)"
                >
                    <x-heroicon-m-document-arrow-down class="w-4 h-4" />
                    <span>Ekspor Excel (.xlsx)</span>
                </button>
                <button
                    type="button"
                    wire:click="exportPdf"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-gray-800 hover:bg-slate-50 dark:hover:bg-gray-700 active:scale-95 text-slate-700 dark:text-gray-200 border border-slate-300 dark:border-gray-600 text-xs font-bold rounded-xl shadow-sm transition"
                    title="Cetak format cetak PDF (A4 Landscape)"
                >
                    <x-heroicon-m-document-text class="w-4 h-4 text-rose-500" />
                    <span>Ekspor PDF</span>
                </button>
            </div>
        </div>

        <!-- Monthly KPI Summary Cards (4-Column Robust CSS Grid) -->
        <div class="recap-kpi-grid">
            <!-- Rate Kehadiran -->
            <div class="recap-card">
                <div>
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Rata-rata Kehadiran</div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">
                        @php
                            $rateColor = $overallRate >= 90 ? 'text-emerald-600 dark:text-emerald-400' : ($overallRate >= 75 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400');
                        @endphp
                        <span class="{{ $rateColor }}">{{ $overallRate }}%</span>
                    </div>
                    <div class="text-[11px] text-slate-500 font-medium mt-1">
                        @if($overallRate >= 90)
                            <span class="inline-flex items-center gap-1 text-emerald-600 font-bold">
                                <x-heroicon-m-check class="w-3.5 h-3.5" /> Sangat Disiplin
                            </span>
                        @elseif($overallRate >= 75)
                            <span class="inline-flex items-center gap-1 text-amber-600 font-bold">
                                <x-heroicon-m-exclamation-circle class="w-3.5 h-3.5" /> Cukup Baik
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-slate-400">
                                Tingkat kehadiran guru
                            </span>
                        @endif
                    </div>
                </div>
                <div class="recap-card-icon bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/50">
                    <x-heroicon-m-check-badge class="w-7 h-7" />
                </div>
            </div>

            <!-- Total Slot -->
            <div class="recap-card">
                <div>
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Total Slot Diabsen</div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ $totalSlotsAll }}</div>
                    <div class="text-[11px] text-slate-500 dark:text-gray-400 font-medium mt-1">
                        Jam tatap muka tercatat
                    </div>
                </div>
                <div class="recap-card-icon bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/50">
                    <x-heroicon-m-calendar-days class="w-7 h-7" />
                </div>
            </div>

            <!-- Hadir Tepat Waktu -->
            <div class="recap-card">
                <div>
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Hadir Tepat Waktu</div>
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ $totalHadirAll }}</div>
                    <div class="text-[11px] text-slate-500 dark:text-gray-400 font-medium mt-1">
                        @if($totalSlotsAll > 0)
                            {{ round(($totalHadirAll / $totalSlotsAll) * 100, 1) }}% dari total sesi
                        @else
                            Slot mengajar tepat waktu
                        @endif
                    </div>
                </div>
                <div class="recap-card-icon bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 border border-teal-100 dark:border-teal-900/50">
                    <x-heroicon-m-clock class="w-7 h-7" />
                </div>
            </div>

            <!-- Terlambat & Kosong -->
            <div class="recap-card">
                <div>
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Terlambat / Kosong</div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">
                        <span class="text-amber-600 dark:text-amber-400">{{ $totalTerlambatAll }}</span>
                        <span class="text-slate-300 dark:text-gray-600 text-sm font-normal">/</span>
                        <span class="text-rose-600 dark:text-rose-400">{{ $totalKosongAll }}</span>
                    </div>
                    <div class="text-[11px] text-slate-500 font-medium mt-1">
                        {{ $totalLateMinutesAll }} total menit keterlambatan
                    </div>
                </div>
                <div class="recap-card-icon bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-900/50">
                    <x-heroicon-m-exclamation-triangle class="w-7 h-7" />
                </div>
            </div>
        </div>

        <!-- Tab Switcher and Live Search Bar -->
        <div class="recap-tab-bar">
            <div class="recap-tab-switcher">
                <button
                    type="button"
                    wire:click="$set('viewMode', 'teacher')"
                    class="recap-tab-btn {{ $viewMode === 'teacher' ? 'active' : '' }}"
                >
                    <x-heroicon-m-user class="w-4 h-4" />
                    <span>Rekap per Guru</span>
                    <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold {{ $viewMode === 'teacher' ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-200 text-slate-600' }}">
                        {{ $rawTeacherCount }}
                    </span>
                </button>
                <button
                    type="button"
                    wire:click="$set('viewMode', 'class')"
                    class="recap-tab-btn {{ $viewMode === 'class' ? 'active' : '' }}"
                >
                    <x-heroicon-m-academic-cap class="w-4 h-4" />
                    <span>Rekap per Kelas</span>
                    <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold {{ $viewMode === 'class' ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-200 text-slate-600' }}">
                        {{ $rawClassCount }}
                    </span>
                </button>
            </div>

            <!-- Search box -->
            <div class="relative w-full sm:w-72">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <x-heroicon-m-magnifying-glass class="w-4 h-4" />
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.250ms="search"
                    placeholder="{{ $viewMode === 'teacher' ? 'Cari nama guru atau NIP...' : 'Cari nama kelas...' }}"
                    class="w-full pl-9 pr-3.5 py-2 text-xs font-semibold rounded-xl border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-slate-800 dark:text-gray-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 shadow-sm transition"
                />
                @if(!empty($search))
                    <button
                        type="button"
                        wire:click="$set('search', '')"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600"
                    >
                        <x-heroicon-m-x-mark class="w-4 h-4" />
                    </button>
                @endif
            </div>
        </div>

        @if($viewMode === 'teacher')
            <!-- Tabel Rekap Guru -->
            <div class="recap-table-wrapper">
                <table class="recap-table">
                    <thead>
                        <tr>
                            <th style="width: 280px;">Nama Pengajar</th>
                            <th class="text-center" style="width: 100px;">Total Slot</th>
                            <th class="text-center text-emerald-600" style="width: 90px;">Hadir</th>
                            <th class="text-center text-amber-600" style="width: 90px;">Terlambat</th>
                            <th class="text-center text-rose-600" style="width: 90px;">Kosong</th>
                            <th class="text-center text-sky-600" style="width: 110px;">Izin / Sakit</th>
                            <th class="text-center" style="width: 110px;">Total Telat</th>
                            <th class="text-center" style="width: 220px;">Tingkat Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teacherRows as $row)
                            @php
                                $numericPct = $row['numeric_pct'];
                                $badgeClass = $numericPct >= 90
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                    : ($numericPct >= 75
                                        ? 'bg-amber-50 text-amber-700 border-amber-200'
                                        : 'bg-rose-50 text-rose-700 border-rose-200');
                                $barClass = $numericPct >= 90 ? 'bg-emerald-500' : ($numericPct >= 75 ? 'bg-amber-500' : 'bg-rose-500');
                            @endphp
                            <tr class="transition-colors">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="recap-avatar">
                                            {{ $row['initials'] }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 dark:text-white text-sm">
                                                {{ $row['name'] }}
                                            </div>
                                            <div class="text-[11px] text-slate-400 font-mono mt-0.5">
                                                NIP: {{ $row['nip'] }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-gray-700 text-slate-800 dark:text-gray-200 font-bold font-mono text-xs">
                                        {{ $row['total_slot'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 font-bold font-mono text-xs border border-emerald-200/60">
                                        {{ $row['hadir'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-amber-50 text-amber-700 font-bold font-mono text-xs border border-amber-200/60">
                                        {{ $row['terlambat'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-rose-50 text-rose-700 font-bold font-mono text-xs border border-rose-200/60">
                                        {{ $row['kosong'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-sky-50 text-sky-700 font-medium font-mono text-xs border border-sky-200/60">
                                        {{ $row['izin'] + $row['sakit'] + $row['dinas'] }}
                                    </span>
                                </td>
                                <td class="text-center font-mono text-xs text-slate-600 dark:text-gray-300">
                                    @if($row['total_late_minutes'] > 0)
                                        <span class="font-bold text-amber-600">{{ $row['total_late_minutes'] }} mnt</span>
                                    @else
                                        <span class="text-slate-400">0 mnt</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-2.5">
                                        <div class="w-24 h-2 bg-slate-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full {{ $barClass }}" style="width: {{ $numericPct }}%"></div>
                                        </div>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold border {{ $badgeClass }}">
                                            {{ $row['percentage'] }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-12 text-center">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-gray-700 text-slate-400 flex items-center justify-center">
                                            <x-heroicon-o-clipboard-document-list class="w-6 h-6" />
                                        </div>
                                        <div class="text-sm font-bold text-slate-700 dark:text-slate-300">
                                            @if(!empty($search))
                                                Tidak ditemukan guru yang cocok dengan pencarian "{{ $search }}"
                                            @else
                                                Belum Ada Data Rekapitulasi Kehadiran
                                            @endif
                                        </div>
                                        <p class="text-xs text-slate-400 max-w-sm">
                                            Data kehadiran guru akan otomatis dirangkum saat sesi absensi dicatat oleh sekretaris kelas atau admin.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <!-- Tabel Rekap Kelas -->
            <div class="recap-table-wrapper">
                <table class="recap-table">
                    <thead>
                        <tr>
                            <th style="width: 250px;">Nama Kelas</th>
                            <th style="width: 140px;">Tahun Ajaran</th>
                            <th class="text-center" style="width: 100px;">Total Slot</th>
                            <th class="text-center text-emerald-600" style="width: 90px;">Hadir</th>
                            <th class="text-center text-amber-600" style="width: 90px;">Terlambat</th>
                            <th class="text-center text-rose-600" style="width: 90px;">Kosong</th>
                            <th class="text-center text-sky-600" style="width: 110px;">Izin / Sakit</th>
                            <th class="text-center" style="width: 220px;">Tingkat Kehadiran Guru</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classRows as $row)
                            @php
                                $numericPct = $row['numeric_pct'];
                                $badgeClass = $numericPct >= 90
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                    : ($numericPct >= 75
                                        ? 'bg-amber-50 text-amber-700 border-amber-200'
                                        : 'bg-rose-50 text-rose-700 border-rose-200');
                                $barClass = $numericPct >= 90 ? 'bg-emerald-500' : ($numericPct >= 75 ? 'bg-amber-500' : 'bg-rose-500');
                            @endphp
                            <tr class="transition-colors">
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold">
                                            <x-heroicon-m-academic-cap class="w-4 h-4" />
                                        </div>
                                        <span class="font-bold text-slate-900 dark:text-white text-sm">
                                            {{ $row['name'] }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-gray-700 text-slate-600 dark:text-gray-300">
                                        {{ $row['academic_year'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-gray-700 text-slate-800 dark:text-gray-200 font-bold font-mono text-xs">
                                        {{ $row['total_slot'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 font-bold font-mono text-xs border border-emerald-200/60">
                                        {{ $row['hadir'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-amber-50 text-amber-700 font-bold font-mono text-xs border border-amber-200/60">
                                        {{ $row['terlambat'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-rose-50 text-rose-700 font-bold font-mono text-xs border border-rose-200/60">
                                        {{ $row['kosong'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-sky-50 text-sky-700 font-medium font-mono text-xs border border-sky-200/60">
                                        {{ $row['izin'] + $row['sakit'] + $row['dinas'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-2.5">
                                        <div class="w-24 h-2 bg-slate-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full {{ $barClass }}" style="width: {{ $numericPct }}%"></div>
                                        </div>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold border {{ $badgeClass }}">
                                            {{ $row['percentage'] }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-12 text-center">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-gray-700 text-slate-400 flex items-center justify-center">
                                            <x-heroicon-o-building-library class="w-6 h-6" />
                                        </div>
                                        <div class="text-sm font-bold text-slate-700 dark:text-slate-300">
                                            @if(!empty($search))
                                                Tidak ditemukan kelas yang cocok dengan pencarian "{{ $search }}"
                                            @else
                                                Belum Ada Data Rekapitulasi Kelas
                                            @endif
                                        </div>
                                        <p class="text-xs text-slate-400 max-w-sm">
                                            Rekapitulasi kelas akan tampil setelah jadwal pelajaran dan kelas terdaftar di sistem.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>

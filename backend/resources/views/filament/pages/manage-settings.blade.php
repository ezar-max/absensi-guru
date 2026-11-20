<x-filament-panels::page>
    <div class="admin-custom settings-layout-grid">
        <!-- Form Pengaturan (Kiri) -->
        <div class="settings-card space-y-6">
            <div class="border-b border-slate-100 dark:border-gray-700 pb-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold shadow-sm">
                            <x-heroicon-m-adjustments-horizontal class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-base">Parameter Aturan Absensi</h3>
                            <p class="text-xs text-slate-500">Konfigurasi batas waktu dan toleransi kehadiran sekolah.</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                        Aturan Utama
                    </span>
                </div>
            </div>

            <form wire:submit="save" class="space-y-6">
                <!-- late_tolerance_min -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300">
                            <x-heroicon-m-clock class="w-4 h-4 text-emerald-500" />
                            <span>Toleransi Keterlambatan</span>
                        </label>
                        <span class="text-xs font-bold text-emerald-600 font-mono">{{ $late_tolerance_min }} Menit</span>
                    </div>

                    <div class="relative">
                        <input
                            type="number"
                            wire:model.live="late_tolerance_min"
                            min="0"
                            max="60"
                            class="w-full rounded-xl border border-slate-300 dark:border-gray-600 bg-slate-50/70 dark:bg-gray-700 text-slate-900 dark:text-gray-100 text-sm font-semibold focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 py-2.5 px-3.5 shadow-sm transition"
                        />
                        <span class="absolute right-3.5 top-2.5 text-xs text-slate-400 font-semibold pointer-events-none">Menit</span>
                    </div>

                    <!-- Preset Chips -->
                    <div class="flex items-center gap-1.5 pt-0.5">
                        <span class="text-[10px] text-slate-400 font-bold uppercase">Pilihan Cepat:</span>
                        <button type="button" wire:click="setLateTolerance(0)" class="preset-chip-btn {{ $late_tolerance_min === 0 ? 'active' : '' }}">0 mnt</button>
                        <button type="button" wire:click="setLateTolerance(5)" class="preset-chip-btn {{ $late_tolerance_min === 5 ? 'active' : '' }}">5 mnt</button>
                        <button type="button" wire:click="setLateTolerance(10)" class="preset-chip-btn {{ $late_tolerance_min === 10 ? 'active' : '' }}">10 mnt (Standar)</button>
                        <button type="button" wire:click="setLateTolerance(15)" class="preset-chip-btn {{ $late_tolerance_min === 15 ? 'active' : '' }}">15 mnt</button>
                    </div>

                    <p class="text-[11px] text-slate-500 dark:text-gray-400 leading-relaxed">
                        Guru yang hadir ≤ <b class="text-slate-700 dark:text-slate-300">{{ $late_tolerance_min }} menit</b> dari jam mulai dihitung <b>Hadir Tepat Waktu</b>. Lebih dari itu dicatat <b>Terlambat</b>.
                    </p>
                    @error('late_tolerance_min') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- absent_threshold_pct -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300">
                            <x-heroicon-m-exclamation-triangle class="w-4 h-4 text-rose-500" />
                            <span>Ambang Batas Pencatatan Kosong</span>
                        </label>
                        <span class="text-xs font-bold text-rose-600 font-mono">{{ $absent_threshold_pct }}% Durasi</span>
                    </div>

                    <div class="relative">
                        <input
                            type="number"
                            wire:model.live="absent_threshold_pct"
                            min="10"
                            max="90"
                            class="w-full rounded-xl border border-slate-300 dark:border-gray-600 bg-slate-50/70 dark:bg-gray-700 text-slate-900 dark:text-gray-100 text-sm font-semibold focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 py-2.5 px-3.5 shadow-sm transition"
                        />
                        <span class="absolute right-3.5 top-2.5 text-xs text-slate-400 font-semibold pointer-events-none">% durasi</span>
                    </div>

                    <!-- Preset Chips -->
                    <div class="flex items-center gap-1.5 pt-0.5">
                        <span class="text-[10px] text-slate-400 font-bold uppercase">Pilihan Cepat:</span>
                        <button type="button" wire:click="setAbsentThreshold(30)" class="preset-chip-btn {{ $absent_threshold_pct === 30 ? 'active' : '' }}">30%</button>
                        <button type="button" wire:click="setAbsentThreshold(50)" class="preset-chip-btn {{ $absent_threshold_pct === 50 ? 'active' : '' }}">50% (Standar)</button>
                        <button type="button" wire:click="setAbsentThreshold(75)" class="preset-chip-btn {{ $absent_threshold_pct === 75 ? 'active' : '' }}">75%</button>
                    </div>

                    <p class="text-[11px] text-slate-500 dark:text-gray-400 leading-relaxed">
                        Sekretaris baru diizinkan mencatat status <b>Kosong</b> setelah <b class="text-slate-700 dark:text-slate-300">{{ $absent_threshold_pct }}% durasi</b> jam pelajaran berjalan untuk menghindari pelaporan prematur.
                    </p>
                    @error('absent_threshold_pct') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- edit_lock_min -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300">
                            <x-heroicon-m-lock-closed class="w-4 h-4 text-indigo-500" />
                            <span>Jendela Koreksi Sekretaris</span>
                        </label>
                        <span class="text-xs font-bold text-indigo-600 font-mono">{{ $edit_lock_min }} Menit</span>
                    </div>

                    <div class="relative">
                        <input
                            type="number"
                            wire:model.live="edit_lock_min"
                            min="1"
                            max="120"
                            class="w-full rounded-xl border border-slate-300 dark:border-gray-600 bg-slate-50/70 dark:bg-gray-700 text-slate-900 dark:text-gray-100 text-sm font-semibold focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 py-2.5 px-3.5 shadow-sm transition"
                        />
                        <span class="absolute right-3.5 top-2.5 text-xs text-slate-400 font-semibold pointer-events-none">Menit</span>
                    </div>

                    <!-- Preset Chips -->
                    <div class="flex items-center gap-1.5 pt-0.5">
                        <span class="text-[10px] text-slate-400 font-bold uppercase">Pilihan Cepat:</span>
                        <button type="button" wire:click="setEditLock(10)" class="preset-chip-btn {{ $edit_lock_min === 10 ? 'active' : '' }}">10 mnt</button>
                        <button type="button" wire:click="setEditLock(15)" class="preset-chip-btn {{ $edit_lock_min === 15 ? 'active' : '' }}">15 mnt (Standar)</button>
                        <button type="button" wire:click="setEditLock(30)" class="preset-chip-btn {{ $edit_lock_min === 30 ? 'active' : '' }}">30 mnt</button>
                    </div>

                    <p class="text-[11px] text-slate-500 dark:text-gray-400 leading-relaxed">
                        Maksimal durasi sekretaris dapat mengoreksi data absensi yang telah di-input. Setelah <b class="text-slate-700 dark:text-slate-300">{{ $edit_lock_min }} menit</b>, pengubahan terkunci dan hanya dapat dilakukan oleh <b>Admin</b>.
                    </p>
                    @error('edit_lock_min') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="pt-4 border-t border-slate-100 dark:border-gray-700 flex flex-col sm:flex-row items-center gap-3">
                    <button
                        type="submit"
                        class="w-full sm:flex-1 inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-500 active:scale-95 rounded-xl shadow-md shadow-indigo-600/25 transition cursor-pointer"
                    >
                        <x-heroicon-m-check class="w-4 h-4" />
                        <span wire:loading.remove>Simpan Pengaturan</span>
                        <span wire:loading>Menyimpan...</span>
                    </button>

                    <button
                        type="button"
                        wire:click="resetDefaults"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-gray-700 border border-slate-300 dark:border-gray-600 rounded-xl transition cursor-pointer"
                        title="Kembalikan ke standar awal"
                    >
                        <x-heroicon-m-arrow-path class="w-4 h-4 text-slate-400" />
                        <span>Default</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Live Calculation Preview & Visual Timeline (Kanan) -->
        <div class="settings-card space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-gray-700 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold shadow-sm">
                        <x-heroicon-m-calculator class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900 dark:text-white text-base">Simulasi Visual Timeline Presensi</h4>
                        <p class="text-xs text-slate-500">Kalkulasi interaktif untuk slot jam pelajaran contoh: <b>07:00 – 09:00</b> (durasi 120 menit).</p>
                    </div>
                </div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Live Reactive
                </span>
            </div>

            @php
                $totalDuration = 120; // 120 menit simulasi
                $tolMin = max(0, min($late_tolerance_min, 60));
                $threshPct = max(10, min($absent_threshold_pct, 90));
                $thresholdMins = (int) ceil($totalDuration * ($threshPct / 100));

                $tolPct = round(($tolMin / $totalDuration) * 100, 1);
                $lateSpan = max(0, $thresholdMins - $tolMin);
                $latePct = round(($lateSpan / $totalDuration) * 100, 1);
                $kosongSpan = max(0, $totalDuration - $thresholdMins);
                $kosongPct = round(($kosongSpan / $totalDuration) * 100, 1);

                $thresholdHour = 7 + (int) floor($thresholdMins / 60);
                $thresholdRemainder = $thresholdMins % 60;
                $threshTimeStr = sprintf('%02d:%02d', $thresholdHour, $thresholdRemainder);
                $tolTimeStr = sprintf('07:%02d', $tolMin);
            @endphp

            {{-- Visual Timeline Graphic Bar --}}
            <div class="space-y-2.5 bg-slate-50 dark:bg-gray-900/40 p-4 rounded-xl border border-slate-200/80 dark:border-gray-700">
                <div class="flex items-center justify-between text-xs font-mono font-bold text-slate-600 dark:text-slate-300">
                    <div class="flex flex-col items-start">
                        <span class="text-[10px] uppercase text-slate-400 font-sans">Mulai</span>
                        <span>07:00</span>
                    </div>
                    <div class="flex flex-col items-center text-emerald-600 font-bold">
                        <span class="text-[10px] uppercase font-sans">Batas Tepat</span>
                        <span>{{ $tolTimeStr }}</span>
                    </div>
                    <div class="flex flex-col items-center text-rose-600 font-bold">
                        <span class="text-[10px] uppercase font-sans">Ambang Kosong</span>
                        <span>{{ $threshTimeStr }}</span>
                    </div>
                    <div class="flex flex-col items-end">
                        <span class="text-[10px] uppercase text-slate-400 font-sans">Selesai</span>
                        <span>09:00</span>
                    </div>
                </div>

                {{-- Multi-segmented visual bar --}}
                <div class="timeline-bar-container shadow-inner">
                    {{-- Zone 1: Hadir Tepat Waktu --}}
                    <div
                        class="timeline-bar-hadir"
                        style="width: {{ $tolPct }}%"
                        title="Zona Hadir Tepat Waktu: 07:00 - {{ $tolTimeStr }} ({{ $tolMin }} menit)"
                    >
                        @if($tolPct > 10)
                            <span class="truncate px-1.5 font-bold">Hadir ({{ $tolMin }}m)</span>
                        @endif
                    </div>

                    {{-- Zone 2: Terlambat --}}
                    <div
                        class="timeline-bar-terlambat"
                        style="width: {{ $latePct }}%"
                        title="Zona Terlambat: {{ $tolTimeStr }} - {{ $threshTimeStr }}"
                    >
                        @if($latePct > 12)
                            <span class="truncate px-1.5 font-bold">Terlambat</span>
                        @endif
                    </div>

                    {{-- Zone 3: Opsi Kosong Terbuka --}}
                    <div
                        class="timeline-bar-kosong"
                        style="width: {{ $kosongPct }}%"
                        title="Zona Kosong Terbuka: {{ $threshTimeStr }} - 09:00"
                    >
                        @if($kosongPct > 14)
                            <span class="truncate px-1.5 font-bold">Kosong Aktif ({{ $threshPct }}%)</span>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between text-[11px] text-slate-500 dark:text-gray-400 pt-1 border-t border-slate-200/50 dark:border-gray-700/50">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <b>Tepat Waktu:</b> ≤ {{ $tolMin }} menit
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                        <b>Terlambat:</b> {{ $tolTimeStr }} s/d {{ $threshTimeStr }}
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                        <b>Kosong Buka:</b> ≥ {{ $threshTimeStr }}
                    </span>
                </div>
            </div>

            {{-- Explanatory Policy Zone Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-1">
                <div class="p-4 rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/50 dark:bg-emerald-950/20 space-y-1.5">
                    <div class="flex items-center gap-2 text-xs font-bold text-emerald-800 dark:text-emerald-300">
                        <div class="w-5 h-5 rounded-md bg-emerald-100 dark:bg-emerald-900/60 flex items-center justify-center text-emerald-700 dark:text-emerald-300">
                            <x-heroicon-m-check class="w-3.5 h-3.5" />
                        </div>
                        <span>Status Hadir Tepat Waktu</span>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-gray-300 leading-relaxed">
                        Guru tiba pukul <b>07:00 s.d. {{ $tolTimeStr }}</b>. Menit keterlambatan otomatis dicatat <b>0 menit</b> dan berkontribusi positif pada rekapitulasi.
                    </p>
                </div>

                <div class="p-4 rounded-xl border border-amber-200 dark:border-amber-800/60 bg-amber-50/50 dark:bg-amber-950/20 space-y-1.5">
                    <div class="flex items-center gap-2 text-xs font-bold text-amber-800 dark:text-amber-300">
                        <div class="w-5 h-5 rounded-md bg-amber-100 dark:bg-amber-900/60 flex items-center justify-center text-amber-700 dark:text-amber-300">
                            <x-heroicon-m-clock class="w-3.5 h-3.5" />
                        </div>
                        <span>Status Terlambat</span>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-gray-300 leading-relaxed">
                        Guru tiba setelah <b>{{ $tolTimeStr }}</b>. Durasi keterlambatan dihitung otomatis sesuai selisih menit kedatangan aktual dengan jam mulai slot.
                    </p>
                </div>

                <div class="p-4 rounded-xl border border-rose-200 dark:border-rose-800/60 bg-rose-50/50 dark:bg-rose-950/20 space-y-1.5">
                    <div class="flex items-center gap-2 text-xs font-bold text-rose-800 dark:text-rose-300">
                        <div class="w-5 h-5 rounded-md bg-rose-100 dark:bg-rose-900/60 flex items-center justify-center text-rose-700 dark:text-rose-300">
                            <x-heroicon-m-x-mark class="w-3.5 h-3.5" />
                        </div>
                        <span>Ambang Pencatatan Kosong</span>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-gray-300 leading-relaxed">
                        Tombol Kosong baru terbuka bagi Sekretaris mulai pukul <b>{{ $threshTimeStr }}</b> ({{ $threshPct }}% durasi jam pelajaran) untuk menghindari salah pencatatan.
                    </p>
                </div>

                <div class="p-4 rounded-xl border border-indigo-200 dark:border-indigo-800/60 bg-indigo-50/50 dark:bg-indigo-950/20 space-y-1.5">
                    <div class="flex items-center gap-2 text-xs font-bold text-indigo-800 dark:text-indigo-300">
                        <div class="w-5 h-5 rounded-md bg-indigo-100 dark:bg-indigo-900/60 flex items-center justify-center text-indigo-700 dark:text-indigo-300">
                            <x-heroicon-m-lock-closed class="w-3.5 h-3.5" />
                        </div>
                        <span>Koreksi Terkunci Otomatis</span>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-gray-300 leading-relaxed">
                        Setelah <b>{{ $edit_lock_min }} menit</b> sejak dicatat, sekretaris tidak dapat mengedit lagi data absensi. Perubahan lanjutan wajib diajukan ke Admin.
                    </p>
                </div>
            </div>

            <!-- Global summary badge bar -->
            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-gray-800/80 border border-slate-200 dark:border-gray-700 flex items-center justify-between text-xs">
                <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300 font-semibold">
                    <x-heroicon-m-shield-check class="w-4 h-4 text-indigo-600" />
                    <span>Kebijakan Aktif Terkoneksi:</span>
                </div>
                <div class="flex items-center gap-2 font-mono text-xs">
                    <span class="px-2 py-0.5 rounded-md bg-white dark:bg-gray-700 border border-slate-200 dark:border-gray-600 font-bold text-emerald-600">Tol: {{ $late_tolerance_min }}m</span>
                    <span class="px-2 py-0.5 rounded-md bg-white dark:bg-gray-700 border border-slate-200 dark:border-gray-600 font-bold text-rose-600">Kosong: {{ $absent_threshold_pct }}%</span>
                    <span class="px-2 py-0.5 rounded-md bg-white dark:bg-gray-700 border border-slate-200 dark:border-gray-600 font-bold text-indigo-600">Lock: {{ $edit_lock_min }}m</span>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>

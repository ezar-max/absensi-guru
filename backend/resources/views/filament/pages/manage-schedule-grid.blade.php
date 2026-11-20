<x-filament-panels::page>
    <div class="admin-custom space-y-6">
        <!-- Toolbar & Class Selector -->
        <div class="timetable-toolbar">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <div style="width: 2rem; height: 2rem; border-radius: 0.5rem; background: #eef2ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                        <x-heroicon-m-academic-cap class="w-4 h-4" />
                    </div>
                    <label for="class-select" style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">
                        Pilih Kelas:
                    </label>
                </div>

                <div class="relative">
                    <select
                        id="class-select"
                        wire:model.live="selectedClassId"
                        class="timetable-select"
                    >
                        <option value="">-- Pilih Kelas --</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->academic_year }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if($selectedClassId && $selectedClass)
                <div class="flex flex-wrap items-center gap-2">
                    <div class="timetable-chip primary">
                        <x-heroicon-m-calendar class="w-3.5 h-3.5" />
                        <span>{{ $totalClassSlots }} Jam Terjadwal</span>
                    </div>

                    <div class="timetable-chip">
                        <x-heroicon-m-user-group class="w-3.5 h-3.5" />
                        <span>{{ $uniqueTeachers }} Guru</span>
                    </div>

                    <div class="timetable-chip">
                        <x-heroicon-m-book-open class="w-3.5 h-3.5" />
                        <span>{{ $uniqueSubjects }} Mapel</span>
                    </div>
                </div>
            @endif
        </div>

        @if($selectedClassId)
            <!-- Timetable Grid Table -->
            <div class="timetable-container">
                <table class="timetable-table">
                    <thead>
                        <tr>
                            <th class="timetable-th" style="width: 85px;">
                                Jam Ke
                            </th>
                            @php
                                $todayIso = \Carbon\Carbon::now()->dayOfWeekIso;
                            @endphp
                            @foreach($days as $dayNum => $dayName)
                                @php
                                    $isToday = ($dayNum === $todayIso);
                                @endphp
                                <th class="timetable-th {{ $isToday ? 'today' : '' }}">
                                    <div style="display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                                        <span>{{ $dayName }}</span>
                                        @if($isToday)
                                            <span style="width: 6px; height: 6px; border-radius: 9999px; background: #4f46e5; display: inline-block;"></span>
                                        @endif
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($periods as $period)
                            <tr>
                                <td class="timetable-time-cell">
                                    <div style="font-weight: 800; font-size: 0.75rem; color: #0f172a;">{{ $period->label }}</div>
                                    <div style="font-family: monospace; font-size: 0.65rem; font-weight: 600; color: #64748b; white-space: nowrap; margin-top: 2px;">
                                        {{ substr($period->start_time, 0, 5) }}–{{ substr($period->end_time, 0, 5) }}
                                    </div>
                                </td>
                                @foreach($days as $dayNum => $dayName)
                                    @php
                                        $slot = $scheduleMatrix[$period->id][$dayNum] ?? null;
                                        $isToday = ($dayNum === $todayIso);
                                    @endphp
                                    <td class="timetable-slot-cell {{ $isToday ? 'today-col' : '' }}">
                                        @if($slot)
                                            <div class="slot-card">
                                                <div class="slot-card-header">
                                                    <span class="slot-subject-name" title="{{ $slot->subject?->name }}">
                                                        {{ $slot->subject?->name }}
                                                    </span>
                                                    <div class="slot-actions">
                                                        <button
                                                            type="button"
                                                            wire:click="openEditSlot({{ $slot->id }})"
                                                            class="slot-action-btn edit"
                                                            title="Ubah Jadwal"
                                                        >
                                                            <svg style="width: 0.875rem; height: 0.875rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                            </svg>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            wire:click="deleteSlot({{ $slot->id }})"
                                                            wire:confirm="Yakin ingin menghapus jadwal ini?"
                                                            class="slot-action-btn delete"
                                                            title="Hapus Jadwal"
                                                        >
                                                            <svg style="width: 0.875rem; height: 0.875rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="slot-teacher-info" title="{{ $slot->teacher?->name }}">
                                                    <svg style="width: 0.8125rem; height: 0.8125rem; color: #4f46e5; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                                    </svg>
                                                    <span>{{ $slot->teacher?->name }}</span>
                                                </div>
                                            </div>
                                        @else
                                            <button
                                                type="button"
                                                wire:click="openCreateSlot({{ $dayNum }}, {{ $period->id }})"
                                                class="slot-empty"
                                            >
                                                <div class="slot-empty-icon">+</div>
                                                <span class="slot-empty-label">Isi Jam</span>
                                            </button>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <!-- Empty State -->
            <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm p-8">
                <div class="w-16 h-16 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 mx-auto flex items-center justify-center mb-4">
                    <x-heroicon-o-calendar-days class="w-8 h-8" />
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-gray-100">Silakan Pilih Kelas Terlebih Dahulu</h3>
                <p class="text-sm text-slate-500 dark:text-gray-400 mt-1 max-w-md mx-auto">
                    Pilih salah satu kelas di dropdown atas untuk melihat dan menyusun jadwal mata pelajaran mingguan.
                </p>
            </div>
        @endif

        <!-- Modal Tambah / Edit Jadwal -->
        @if($isModalOpen)
            <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 dark:border-gray-700 space-y-5 animate-in fade-in duration-200">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-gray-700 pb-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 flex items-center justify-center">
                                <x-heroicon-m-calendar class="w-5 h-5" />
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-gray-100">
                                {{ $editingScheduleId ? 'Ubah Jadwal Pelajaran' : 'Tambah Jadwal Pelajaran' }}
                            </h3>
                        </div>
                        <button type="button" wire:click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-gray-200 transition">
                            <x-heroicon-o-x-mark class="w-6 h-6" />
                        </button>
                    </div>

                    <div class="space-y-4 text-sm">
                        <!-- Info Slot Badge -->
                        @php
                            $pInfo = \App\Models\Period::find($modalPeriodId);
                        @endphp
                        <div class="bg-slate-50 dark:bg-gray-900/50 p-3.5 rounded-xl border border-slate-200/70 dark:border-gray-700 flex flex-wrap items-center justify-between gap-2 text-xs">
                            <div class="flex items-center gap-1.5 font-semibold text-slate-700 dark:text-gray-300">
                                <span class="text-slate-400">Hari:</span>
                                <span class="px-2 py-0.5 rounded-md bg-indigo-100 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-300 font-bold">
                                    {{ $days[$modalDayOfWeek] ?? '-' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5 font-semibold text-slate-700 dark:text-gray-300">
                                <span class="text-slate-400">Jam:</span>
                                <span class="font-mono text-slate-800 dark:text-gray-200">
                                    {{ $pInfo?->label }} ({{ substr($pInfo?->start_time, 0, 5) }} – {{ substr($pInfo?->end_time, 0, 5) }})
                                </span>
                            </div>
                        </div>

                        <!-- Pilihan Mapel -->
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-gray-300 mb-1.5">
                                Mata Pelajaran <span class="text-rose-500">*</span>
                            </label>
                            <select
                                wire:model="modalSubjectId"
                                class="w-full rounded-xl border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 py-2.5 px-3 shadow-sm transition"
                            >
                                <option value="">-- Pilih Mata Pelajaran --</option>
                                @foreach(\App\Models\Subject::orderBy('name')->get() as $subj)
                                    <option value="{{ $subj->id }}">{{ $subj->name }} ({{ $subj->code }})</option>
                                @endforeach
                            </select>
                            @error('modalSubjectId') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Pilihan Guru dengan Deteksi Bentrok -->
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-gray-300 mb-1.5">
                                Guru Pengampu <span class="text-rose-500">*</span>
                            </label>
                            <select
                                wire:model="modalTeacherId"
                                class="w-full rounded-xl border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 py-2.5 px-3 shadow-sm transition"
                            >
                                <option value="">-- Pilih Guru Pengampu --</option>
                                @foreach($this->teacherOptions as $opt)
                                    <option
                                        value="{{ $opt['id'] }}"
                                        @if($opt['is_conflict']) class="text-rose-600 font-semibold bg-rose-50" @endif
                                    >
                                        {{ $opt['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('modalTeacherId') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror

                            <div class="mt-2 p-2.5 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-[11px] text-amber-800 dark:text-amber-300 flex items-start gap-2">
                                <x-heroicon-m-exclamation-triangle class="w-4 h-4 shrink-0 text-amber-600 mt-0.5" />
                                <span>Guru yang bertanda <b>[BENTROK]</b> sudah terjadwal mengajar di kelas lain pada jam & hari yang sama. Sistem menolak duplikasi jadwal.</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-gray-700">
                        <button
                            type="button"
                            wire:click="closeModal"
                            class="px-4 py-2 text-sm font-semibold text-slate-700 dark:text-gray-300 bg-slate-100 hover:bg-slate-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-xl transition"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            wire:click="saveSlot"
                            class="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-md shadow-indigo-600/30 transition hover:scale-[1.01]"
                        >
                            Simpan Jadwal
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>

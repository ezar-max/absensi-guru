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
            <div x-data="{}" @keydown.escape.window="$wire.closeModal()">
                <template x-teleport="body">
                    <div class="grid-modal-backdrop" wire:key="schedule-modal-backdrop" wire:click.self="closeModal">
                        <div class="grid-modal-card" wire:key="schedule-modal-card">
                            <div class="grid-modal-header">
                                <div class="grid-modal-title-wrap">
                                    <div class="grid-modal-icon-badge">
                                        <svg style="width: 1.25rem; height: 1.25rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="grid-modal-title">
                                            {{ $editingScheduleId ? 'Ubah Jadwal Pelajaran' : 'Tambah Jadwal Pelajaran' }}
                                        </h3>
                                        <p class="grid-modal-subtitle">
                                            {{ $selectedClass?->name }} • {{ $selectedClass?->academic_year }}
                                        </p>
                                    </div>
                                </div>
                                <button type="button" wire:click="closeModal" class="grid-modal-close-btn" title="Tutup">
                                    <svg style="width: 1.25rem; height: 1.25rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Info Slot Badge -->
                            @php
                                $pInfo = \App\Models\Period::find($modalPeriodId);
                            @endphp
                            <div class="grid-modal-info-bar">
                                <div class="grid-modal-info-item">
                                    <span class="grid-modal-info-label">Hari:</span>
                                    <span class="grid-modal-info-pill">
                                        {{ $days[$modalDayOfWeek] ?? '-' }}
                                    </span>
                                </div>
                                <div class="grid-modal-info-item">
                                    <span class="grid-modal-info-label">Jam:</span>
                                    <span class="grid-modal-info-pill" style="background:#f1f5f9; color:#0f172a; border-color:#e2e8f0; font-family: monospace;">
                                        {{ $pInfo?->label }} ({{ substr($pInfo?->start_time, 0, 5) }} – {{ substr($pInfo?->end_time, 0, 5) }})
                                    </span>
                                </div>
                            </div>

                            <!-- Pilihan Mapel -->
                            <div class="grid-modal-field">
                                <label class="grid-modal-label">
                                    <span>Mata Pelajaran</span>
                                    <span class="grid-modal-label-required">*</span>
                                </label>
                                <div class="grid-modal-select-wrapper">
                                    <select
                                        wire:model="modalSubjectId"
                                        class="grid-modal-select"
                                    >
                                        <option value="">-- Pilih Mata Pelajaran --</option>
                                        @foreach(\App\Models\Subject::orderBy('name')->get() as $subj)
                                            <option value="{{ $subj->id }}">{{ $subj->name }} ({{ $subj->code }})</option>
                                        @endforeach
                                    </select>
                                    <svg class="grid-modal-select-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                                @error('modalSubjectId') <span class="grid-modal-error">{{ $message }}</span> @enderror
                            </div>

                            <!-- Pilihan Guru dengan Deteksi Bentrok -->
                            <div class="grid-modal-field">
                                <label class="grid-modal-label">
                                    <span>Guru Pengampu</span>
                                    <span class="grid-modal-label-required">*</span>
                                </label>
                                <div class="grid-modal-select-wrapper">
                                    <select
                                        wire:model="modalTeacherId"
                                        class="grid-modal-select"
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
                                    <svg class="grid-modal-select-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                                @error('modalTeacherId') <span class="grid-modal-error">{{ $message }}</span> @enderror

                                <div class="grid-modal-conflict-box">
                                    <svg class="grid-modal-conflict-icon" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="grid-modal-conflict-text">Guru yang bertanda <b>[BENTROK]</b> sudah terjadwal mengajar di kelas lain pada jam & hari yang sama. Sistem menolak duplikasi jadwal.</span>
                                </div>
                            </div>

                            <div class="grid-modal-footer">
                                <button
                                    type="button"
                                    wire:click="closeModal"
                                    class="grid-modal-btn-cancel"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    wire:click="saveSlot"
                                    class="grid-modal-btn-save"
                                >
                                    <svg style="width: 1rem; height: 1rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Simpan Jadwal</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        @endif
    </div>
</x-filament-panels::page>

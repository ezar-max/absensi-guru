<x-filament-panels::page>
    <div class="admin-custom space-y-6">
        <!-- Toolbar & Teacher Selector -->
        <div class="timetable-toolbar">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <div style="width: 2rem; height: 2rem; border-radius: 0.5rem; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                        <x-heroicon-m-user class="w-4 h-4" />
                    </div>
                    <label for="teacher-select" style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">
                        Pilih Guru:
                    </label>
                </div>

                <div class="relative">
                    <select
                        id="teacher-select"
                        wire:model.live="selectedTeacherId"
                        class="timetable-select"
                        style="min-width: 280px;"
                    >
                        <option value="">-- Pilih Guru --</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}">{{ $t->name }} {{ $t->nip ? '('.$t->nip.')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if($selectedTeacherId && $selectedTeacher)
                @php
                    $targetHours = 24;
                    $loadPct = min(100, round(($totalHours / $targetHours) * 100));
                    $isSufficient = ($totalHours >= $targetHours);
                @endphp
                <div class="flex flex-wrap items-center gap-3 bg-slate-50 dark:bg-gray-900/50 px-3.5 py-2 rounded-xl border border-slate-200/80 dark:border-gray-700">
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Beban Mengajar:</div>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span class="text-sm font-extrabold text-slate-900 dark:text-white">{{ $totalHours }} JP</span>
                            <span class="text-[11px] text-slate-400">/ 24 JP</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $isSufficient ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $loadPct }}%
                            </span>
                        </div>
                    </div>

                    {{-- Mini Progress Bar --}}
                    <div style="width: 5rem; height: 0.5rem; border-radius: 9999px; background: #e2e8f0; overflow: hidden;" class="hidden sm:block">
                        <div
                            style="height: 100%; border-radius: 9999px; transition: width 0.3s ease; width: {{ $loadPct }}%; background: {{ $isSufficient ? '#10b981' : '#f59e0b' }};"
                        ></div>
                    </div>
                </div>
            @endif
        </div>

        @if($selectedTeacherId)
            <!-- Timetable Matrix Read-Only -->
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
                                            <span style="width: 6px; height: 6px; border-radius: 9999px; background: #10b981; display: inline-block;"></span>
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
                                                    <span class="slot-subject-name">
                                                        {{ $slot->schoolClass?->name }}
                                                    </span>
                                                    <span style="font-size: 0.625rem; font-weight: 700; padding: 0.1rem 0.45rem; border-radius: 9999px; background: #ecfdf5; color: #047857;">
                                                        Kelas
                                                    </span>
                                                </div>
                                                <div class="slot-teacher-info" title="{{ $slot->subject?->name }}">
                                                    <x-heroicon-m-book-open class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                                    <span>{{ $slot->subject?->name }}</span>
                                                </div>
                                            </div>
                                        @else
                                            <div style="height: 4.5rem; display: flex; align-items: center; justify-content: center; color: #cbd5e1; font-size: 0.875rem;">
                                                —
                                            </div>
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
                <div class="w-16 h-16 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 mx-auto flex items-center justify-center mb-4">
                    <x-heroicon-o-user class="w-8 h-8" />
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-gray-100">Silakan Pilih Guru Terlebih Dahulu</h3>
                <p class="text-sm text-slate-500 dark:text-gray-400 mt-1 max-w-md mx-auto">
                    Pilih nama guru di dropdown atas untuk melihat jadwal mengajar mingguan dan memantau beban jam pelajaran (JP).
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>

import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  AppState,
  Modal,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { BookOpen, CalendarDays, CheckCircle, ChevronDown, Clock, Star } from 'lucide-react-native';
import { useTheme } from '../../src/context/ThemeContext';
import { Radius, Spacing, Typography } from '../../src/constants/theme';
import { Toast } from '../../src/components/Toast';
import { api, getErrorMessage } from '../../src/services/api';

interface ClassOption {
  id: number;
  name: string;
  major: string;
}

interface PeriodOption {
  id: number;
  label: string;
  start: string;
  end: string;
}

interface SubjectOption {
  id: number;
  name: string;
}

interface TeacherOption {
  id: number;
  name: string;
  subject_ids: number[];
}

interface RatingOption {
  value: string;
  label: string;
  criteria: string;
}

interface OptionsResponse {
  classes: ClassOption[];
  periods: PeriodOption[];
  subjects: SubjectOption[];
  teachers: TeacherOption[];
  ratings: RatingOption[];
}

interface TodayEntry {
  schedule_id: number | null;
  evaluation_id: number | null;
  class_id: number;
  period_id: number;
  subject_id: number;
  teacher_id: number;
  class: string;
  period: string;
  start: string;
  end: string;
  subject: string;
  teacher: string;
  rating: string | null;
  rating_label: string | null;
  teacher_present: boolean | null;
  note: string | null;
  created_at: string | null;
}

interface TodayResponse {
  server_time: string;
  active_period_id: number | null;
  holiday: { name: string; date: string } | null;
  data: TodayEntry[];
}

type PickerName = 'major' | 'class' | 'period' | 'subject' | 'teacher';

interface PickerChoice {
  id: number;
  label: string;
  detail?: string;
}

export default function TeacherRatingScreen() {
  const { colors, isDark } = useTheme();
  const [classes, setClasses] = useState<ClassOption[]>([]);
  const [periods, setPeriods] = useState<PeriodOption[]>([]);
  const [subjects, setSubjects] = useState<SubjectOption[]>([]);
  const [teachers, setTeachers] = useState<TeacherOption[]>([]);
  const [ratingOptions, setRatingOptions] = useState<RatingOption[]>([]);
  const [todayEntries, setTodayEntries] = useState<TodayEntry[]>([]);
  const [serverTime, setServerTime] = useState(new Date().toISOString());
  const [activePeriodId, setActivePeriodId] = useState<number | null>(null);
  const [holiday, setHoliday] = useState<TodayResponse['holiday']>(null);
  const [classId, setClassId] = useState<number | null>(null);
  const [major, setMajor] = useState<string | null>(null);
  const [periodId, setPeriodId] = useState<number | null>(null);
  const [subjectId, setSubjectId] = useState<number | null>(null);
  const [teacherId, setTeacherId] = useState<number | null>(null);
  const [rating, setRating] = useState<string | null>(null);
  const [teacherPresent, setTeacherPresent] = useState<boolean | null>(null);
  const [note, setNote] = useState('');
  const [picker, setPicker] = useState<PickerName | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [toastMessage, setToastMessage] = useState('');
  const [toastType, setToastType] = useState<'success' | 'error'>('success');
  const [toastVisible, setToastVisible] = useState(false);

  const showToast = (message: string, type: 'success' | 'error') => {
    setToastMessage(message);
    setToastType(type);
    setToastVisible(true);
  };

  const fetchData = useCallback(async (manualRefresh = false) => {
    if (manualRefresh) setRefreshing(true);

    try {
      const [todayResponse, optionsResponse] = await Promise.all([
        api.get<TodayResponse>('/teacher-ratings/today'),
        api.get<OptionsResponse>('/teacher-ratings/options'),
      ]);
      const today = todayResponse.data;
      const options = optionsResponse.data;

      setTodayEntries(today.data);
      setServerTime(today.server_time);
      setActivePeriodId(today.active_period_id);
      setHoliday(today.holiday);
      setClasses(options.classes);
      setPeriods(options.periods);
      setSubjects(options.subjects);
      setTeachers(options.teachers);
      setRatingOptions(options.ratings);
      setPeriodId((current) => today.active_period_id ?? current);
    } catch (error) {
      showToast(getErrorMessage(error), 'error');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    void fetchData();
    const interval = setInterval(() => void fetchData(), 60000);
    const subscription = AppState.addEventListener('change', (state) => {
      if (state === 'active') void fetchData();
    });

    return () => {
      clearInterval(interval);
      subscription.remove();
    };
  }, [fetchData]);

  const formattedDate = useMemo(() => new Date(serverTime).toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }), [serverTime]);

  const selectedClass = classes.find((item) => item.id === classId);
  const selectedPeriod = periods.find((item) => item.id === periodId);
  const selectedSubject = subjects.find((item) => item.id === subjectId);
  const selectedTeacher = teachers.find((item) => item.id === teacherId);
  const subjectTeachers = teachers.filter((teacher) => teacher.subject_ids.includes(subjectId ?? -1));
  const savedEntries = todayEntries.filter((entry) => entry.rating !== null);
  const availableMajors = useMemo(
    () => Array.from(new Set(classes.map((item) => item.major.trim() || 'Umum'))).sort(),
    [classes]
  );
  const filteredClasses = classes.filter((item) => (item.major.trim() || 'Umum') === major);
  const canSubmit = Boolean(
    classId
    && periodId
    && subjectId
    && teacherId
    && rating
    && teacherPresent !== null
    && activePeriodId === periodId
    && !holiday
    && !saving
  );

  const pickerTitle = picker === 'major'
    ? 'Pilih Jurusan'
    : picker === 'class'
    ? 'Pilih Kelas'
    : picker === 'period'
      ? 'Pilih Jam Pelajaran'
      : picker === 'subject'
        ? 'Pilih Mata Pelajaran'
        : 'Pilih Guru';

  const pickerChoices = useMemo<PickerChoice[]>(() => {
    if (picker === 'major') {
      return availableMajors.map((name, id) => ({ id, label: name }));
    }
    if (picker === 'class') {
      return filteredClasses.map((item) => ({
        id: item.id,
        label: item.name,
        detail: item.major || undefined,
      }));
    }
    if (picker === 'period') {
      return periods.map((item) => ({
        id: item.id,
        label: item.label,
        detail: `${item.start} - ${item.end}`,
      }));
    }
    if (picker === 'subject') {
      return subjects.map((item) => ({ id: item.id, label: item.name }));
    }
    if (picker === 'teacher') {
      return subjectTeachers.map((item) => ({ id: item.id, label: item.name }));
    }
    return [];
  }, [availableMajors, filteredClasses, periods, picker, subjectTeachers, subjects]);

  const selectPickerChoice = (choice: PickerChoice) => {
    if (picker === 'major') {
      setMajor(choice.label);
      setClassId(null);
    }
    if (picker === 'class') setClassId(choice.id);
    if (picker === 'period') setPeriodId(choice.id);
    if (picker === 'subject') {
      setSubjectId(choice.id);
      setTeacherId(null);
    }
    if (picker === 'teacher') setTeacherId(choice.id);
    setPicker(null);
  };

  const submitRating = async () => {
    if (!canSubmit || !classId || !periodId || !subjectId || !teacherId || !rating || teacherPresent === null) {
      return;
    }

    setSaving(true);
    try {
      await api.post('/teacher-ratings', {
        class_id: classId,
        period_id: periodId,
        subject_id: subjectId,
        teacher_id: teacherId,
        rating,
        teacher_present: teacherPresent,
        note: note.trim() || null,
      });
      showToast('Penilaian berhasil disimpan.', 'success');
      await fetchData();
    } catch (error) {
      showToast(getErrorMessage(error), 'error');
    } finally {
      setSaving(false);
    }
  };

  const selectField = (
    label: string,
    value: string | undefined,
    placeholder: string,
    pickerName: PickerName,
    disabled = false
  ) => (
    <View style={styles.fieldGroup}>
      <Text style={[styles.fieldLabel, { color: colors.text }]}>{label}</Text>
      <TouchableOpacity
        activeOpacity={0.8}
        disabled={disabled}
        onPress={() => setPicker(pickerName)}
        style={[
          styles.selectField,
          { backgroundColor: colors.bg, borderColor: colors.border },
          disabled && styles.disabledField,
        ]}
      >
        <Text style={[styles.selectText, { color: value ? colors.text : colors.textMuted }]}>
          {value ?? placeholder}
        </Text>
        <ChevronDown size={18} color={colors.textMuted} />
      </TouchableOpacity>
    </View>
  );

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: colors.bg }]}>
      <Toast
        visible={toastVisible}
        message={toastMessage}
        type={toastType}
        onDismiss={() => setToastVisible(false)}
      />

      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => void fetchData(true)}
            tintColor={colors.primary}
            colors={[colors.primary]}
          />
        }
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.brandRow}>
          <View style={[styles.brandDot, { backgroundColor: colors.accentCyan }]} />
          <Text style={[styles.brandText, { color: colors.accentCyan }]}>SMART EVALUATION</Text>
        </View>

        <View style={styles.header}>
          <View style={[styles.headerIcon, { backgroundColor: isDark ? '#eaf2ff' : colors.primarySoft }]}>
            <Star size={31} color="#2563eb" />
          </View>
          <View style={styles.headerCopy}>
            <Text style={[styles.title, { color: colors.text }]}>Penilaian Guru</Text>
            <Text style={[styles.subtitle, { color: colors.textMuted }]}>
              Isi penilaian untuk jam pelajaran yang sedang berlangsung.
            </Text>
          </View>
        </View>

        <View style={[styles.formCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
          {loading ? (
            <ActivityIndicator style={styles.loadingState} size="large" color={colors.primary} />
          ) : (
            <>
              {selectField('Jurusan', major ?? undefined, 'Pilih jurusan', 'major')}
              {selectField(
                'Kelas',
                selectedClass?.name,
                major ? 'Pilih kelas' : 'Pilih jurusan terlebih dahulu',
                'class',
                !major
              )}
              {selectField(
                'Jam Pelajaran',
                selectedPeriod ? `${selectedPeriod.label} · ${selectedPeriod.start}-${selectedPeriod.end}` : undefined,
                'Pilih jam pelajaran',
                'period'
              )}
              {selectField('Mata Pelajaran', selectedSubject?.name, 'Pilih mata pelajaran', 'subject')}
              {selectField(
                'Guru',
                selectedTeacher?.name,
                subjectId ? 'Pilih guru' : 'Pilih mata pelajaran terlebih dahulu',
                'teacher',
                !subjectId
              )}

              <Text style={[styles.fieldLabel, { color: colors.text }]}>Kehadiran Guru</Text>
              <View style={styles.presenceChoices}>
                {[
                  { value: true, label: 'Guru masuk' },
                  { value: false, label: 'Guru tidak masuk' },
                ].map((option) => {
                  const selected = teacherPresent === option.value;
                  return (
                    <TouchableOpacity
                      key={option.label}
                      activeOpacity={0.8}
                      accessibilityRole="radio"
                      accessibilityState={{ selected }}
                      onPress={() => setTeacherPresent(option.value)}
                      style={[
                        styles.presenceOption,
                        {
                          backgroundColor: selected ? colors.primarySoft : colors.surface,
                          borderColor: selected ? colors.primary : colors.border,
                        },
                      ]}
                    >
                      <View
                        style={[
                          styles.presenceCheckbox,
                          { borderColor: selected ? colors.primary : colors.border },
                          selected && { backgroundColor: colors.primary },
                        ]}
                      >
                        {selected && <CheckCircle size={16} color="#ffffff" />}
                      </View>
                      <Text style={[styles.presenceLabel, { color: colors.text }]}>{option.label}</Text>
                    </TouchableOpacity>
                  );
                })}
              </View>

              {classes.length === 0 && (
                <Text style={[styles.helperText, { color: '#dc2626' }]}>
                  Akun ini belum ditugaskan ke kelas. Hubungi admin.
                </Text>
              )}
              {subjectId !== null && subjectTeachers.length === 0 && (
                <Text style={[styles.helperText, { color: colors.textMuted }]}>
                  Belum ada guru aktif yang terdaftar untuk mata pelajaran ini.
                </Text>
              )}
              {holiday ? (
                <View style={[styles.notice, { backgroundColor: '#fef2f2' }]}>
                  <Text style={[styles.noticeText, { color: '#b91c1c' }]}>
                    Hari ini libur sekolah ({holiday.name}); penilaian tidak dapat dikirim.
                  </Text>
                </View>
              ) : activePeriodId === null ? (
                <View style={[styles.notice, { backgroundColor: colors.primarySoft }]}>
                  <Clock size={16} color={colors.primary} />
                  <Text style={[styles.noticeText, { color: colors.primary }]}>
                    Pengisian hanya dapat disimpan saat jam pelajaran sedang berlangsung.
                  </Text>
                </View>
              ) : periodId !== activePeriodId ? (
                <View style={[styles.notice, { backgroundColor: colors.primarySoft }]}>
                  <Clock size={16} color={colors.primary} />
                  <Text style={[styles.noticeText, { color: colors.primary }]}>
                    Pilih jam pelajaran yang sedang berlangsung untuk mengisi penilaian.
                  </Text>
                </View>
              ) : null}

              <Text style={[styles.fieldLabel, { color: colors.text }]}>Kriteria Penilaian</Text>
              {ratingOptions.map((option) => {
                const isSelected = rating === option.value;
                return (
                  <TouchableOpacity
                    key={option.value}
                    activeOpacity={0.8}
                    onPress={() => setRating(option.value)}
                    style={[
                      styles.ratingOption,
                      {
                        borderColor: isSelected ? colors.primary : colors.border,
                        backgroundColor: isSelected ? colors.primarySoft : colors.surface,
                      },
                    ]}
                  >
                    <View style={styles.ratingCopy}>
                      <Text style={[styles.ratingLabel, { color: colors.text }]}>{option.label}</Text>
                      <Text style={[styles.ratingCriteria, { color: colors.textMuted }]}>{option.criteria}</Text>
                    </View>
                    {isSelected && <CheckCircle size={18} color={colors.primary} />}
                  </TouchableOpacity>
                );
              })}

              <Text style={[styles.fieldLabel, { color: colors.text }]}>Catatan (opsional)</Text>
              <TextInput
                value={note}
                onChangeText={setNote}
                placeholder="Tambahkan catatan jika diperlukan"
                placeholderTextColor={colors.textMuted}
                multiline
                maxLength={500}
                textAlignVertical="top"
                style={[
                  styles.noteInput,
                  { color: colors.text, backgroundColor: colors.bg, borderColor: colors.border },
                ]}
              />

              <TouchableOpacity
                activeOpacity={0.85}
                disabled={!canSubmit}
                onPress={() => void submitRating()}
                style={[styles.submitButton, { backgroundColor: colors.primary }, !canSubmit && styles.disabledButton]}
              >
                {saving ? (
                  <ActivityIndicator color="#ffffff" />
                ) : (
                  <Text style={styles.submitButtonText}>Simpan Penilaian</Text>
                )}
              </TouchableOpacity>
            </>
          )}
        </View>

        <View style={styles.savedHeader}>
          <View style={styles.savedHeadingCopy}>
            <View style={styles.savedTitleRow}>
              <BookOpen size={17} color={colors.primary} />
              <Text style={[styles.savedTitle, { color: colors.text }]}>Penilaian Hari Ini</Text>
            </View>
            <Text style={[styles.savedDate, { color: colors.textMuted }]}>{formattedDate}</Text>
          </View>
          <Text style={[styles.savedCount, { color: colors.textMuted }]}>{savedEntries.length} tersimpan</Text>
        </View>
        {savedEntries.length === 0 ? (
          <Text style={[styles.emptyText, { color: colors.textMuted }]}>
            Penilaian yang berhasil disimpan hari ini akan muncul di sini.
          </Text>
        ) : (
          savedEntries.map((entry, index) => (
            <View
              key={entry.evaluation_id ?? entry.schedule_id ?? index}
              style={[styles.savedCard, { backgroundColor: colors.surface, borderColor: colors.border }]}
            >
              <View style={styles.savedTime}>
                <Clock size={14} color={colors.primary} />
                <Text style={[styles.savedTimeText, { color: colors.text }]}>
                  {entry.period} · {entry.start}-{entry.end}
                </Text>
              </View>
              <Text style={[styles.savedSubject, { color: colors.text }]}>{entry.subject}</Text>
              <Text style={[styles.savedDetails, { color: colors.textMuted }]}>
                {entry.teacher} · {entry.class}
              </Text>
              <Text style={[styles.savedPresence, { color: entry.teacher_present === false ? '#dc2626' : '#059669' }]}>
                {entry.teacher_present === null
                  ? 'Status kehadiran belum dicatat'
                  : entry.teacher_present ? 'Guru masuk' : 'Guru tidak masuk'}
              </Text>
              <View style={styles.savedRating}>
                <Star size={14} color="#059669" />
                <Text style={styles.savedRatingText}>{entry.rating_label}</Text>
              </View>
            </View>
          ))
        )}
      </ScrollView>

      <Modal
        visible={picker !== null}
        transparent
        animationType="slide"
        onRequestClose={() => setPicker(null)}
      >
        <View style={styles.modalRoot}>
          <Pressable style={StyleSheet.absoluteFill} onPress={() => setPicker(null)} />
          <View style={[styles.pickerCard, { backgroundColor: colors.surface }]}>
            <Text style={[styles.pickerTitle, { color: colors.text }]}>{pickerTitle}</Text>
            <ScrollView keyboardShouldPersistTaps="handled">
              {pickerChoices.map((choice) => (
                <TouchableOpacity
                  key={choice.id}
                  activeOpacity={0.8}
                  onPress={() => selectPickerChoice(choice)}
                  style={[styles.pickerOption, { borderBottomColor: colors.border }]}
                >
                  <View style={styles.pickerOptionCopy}>
                    <Text style={[styles.pickerOptionLabel, { color: colors.text }]}>{choice.label}</Text>
                    {choice.detail && (
                      <Text style={[styles.pickerOptionDetail, { color: colors.textMuted }]}>{choice.detail}</Text>
                    )}
                  </View>
                  {((picker === 'major' && choice.label === major)
                    || (picker === 'class' && choice.id === classId)
                    || (picker === 'period' && choice.id === periodId)
                    || (picker === 'subject' && choice.id === subjectId)
                    || (picker === 'teacher' && choice.id === teacherId))
                    && <CheckCircle size={18} color={colors.primary} />}
                </TouchableOpacity>
              ))}
              {pickerChoices.length === 0 && (
                <Text style={[styles.emptyText, { color: colors.textMuted }]}>Pilihan belum tersedia.</Text>
              )}
            </ScrollView>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  content: { padding: Spacing.lg, paddingBottom: 112 },
  brandRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'flex-end', gap: 7, marginTop: 4, marginBottom: 15 },
  brandDot: { width: 7, height: 7, borderRadius: 4 },
  brandText: { fontFamily: Typography.fontFamily.bold, fontSize: 10, letterSpacing: 1.7 },
  header: { flexDirection: 'row', alignItems: 'center', marginBottom: 22 },
  headerIcon: { width: 70, height: 70, borderRadius: 20, alignItems: 'center', justifyContent: 'center', marginRight: 14 },
  headerCopy: { flex: 1 },
  title: { fontFamily: Typography.fontFamily.bold, fontSize: 25 },
  subtitle: { fontFamily: Typography.fontFamily.medium, fontSize: 13, lineHeight: 19, marginTop: 3 },
  formCard: { borderWidth: 1, borderRadius: 27, padding: 20 },
  fieldGroup: { marginBottom: 17 },
  fieldLabel: { fontFamily: Typography.fontFamily.bold, fontSize: 15, marginBottom: 9 },
  selectField: { minHeight: 66, borderWidth: 1, borderRadius: 19, paddingHorizontal: 17, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  selectText: { flex: 1, fontFamily: Typography.fontFamily.medium, fontSize: 15 },
  disabledField: { opacity: 0.55 },
  helperText: { fontFamily: Typography.fontFamily.medium, fontSize: 11, marginTop: -4, marginBottom: 10 },
  notice: { flexDirection: 'row', alignItems: 'center', gap: 7, borderRadius: 12, padding: 10, marginBottom: 14 },
  noticeText: { flex: 1, fontFamily: Typography.fontFamily.medium, fontSize: 11, lineHeight: 16 },
  loadingState: { paddingVertical: 36 },
  ratingOption: { minHeight: 78, borderWidth: 1, borderRadius: 17, paddingHorizontal: 15, paddingVertical: 12, flexDirection: 'row', alignItems: 'center', marginBottom: 10 },
  ratingCopy: { flex: 1 },
  ratingLabel: { fontFamily: Typography.fontFamily.bold, fontSize: 15 },
  ratingCriteria: { fontFamily: Typography.fontFamily.regular, fontSize: 12, marginTop: 4 },
  presenceChoices: { flexDirection: 'row', gap: 10, marginBottom: 17 },
  presenceOption: { flex: 1, minHeight: 52, borderWidth: 1, borderRadius: 14, paddingHorizontal: 10, flexDirection: 'row', alignItems: 'center', gap: 8 },
  presenceCheckbox: { width: 20, height: 20, borderWidth: 1.5, borderRadius: 5, alignItems: 'center', justifyContent: 'center' },
  presenceLabel: { flex: 1, fontFamily: Typography.fontFamily.semiBold, fontSize: 12 },
  noteInput: { minHeight: 76, borderWidth: 1, borderRadius: 12, padding: 10, fontFamily: Typography.fontFamily.regular, fontSize: 13, marginBottom: 12 },
  submitButton: { height: 48, borderRadius: 14, alignItems: 'center', justifyContent: 'center', marginTop: 4 },
  submitButtonText: { color: '#ffffff', fontFamily: Typography.fontFamily.bold, fontSize: 14 },
  disabledButton: { opacity: 0.48 },
  savedHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 22, marginBottom: 9 },
  savedHeadingCopy: { flex: 1 },
  savedTitleRow: { flexDirection: 'row', alignItems: 'center', gap: 7 },
  savedTitle: { fontFamily: Typography.fontFamily.bold, fontSize: 15 },
  savedDate: { fontFamily: Typography.fontFamily.medium, fontSize: 10, marginTop: 3, marginLeft: 24 },
  savedCount: { fontFamily: Typography.fontFamily.medium, fontSize: 10 },
  emptyText: { fontFamily: Typography.fontFamily.regular, fontSize: 11, lineHeight: 17, paddingVertical: 8 },
  savedCard: { borderWidth: 1, borderRadius: Radius.lg, padding: 12, marginBottom: 9 },
  savedTime: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  savedTimeText: { fontFamily: Typography.fontFamily.bold, fontSize: 11 },
  savedSubject: { fontFamily: Typography.fontFamily.bold, fontSize: 13, marginTop: 9 },
  savedDetails: { fontFamily: Typography.fontFamily.regular, fontSize: 10, marginTop: 3 },
  savedPresence: { fontFamily: Typography.fontFamily.semiBold, fontSize: 10, marginTop: 6 },
  savedRating: { flexDirection: 'row', alignItems: 'center', gap: 5, marginTop: 8 },
  savedRatingText: { color: '#059669', fontFamily: Typography.fontFamily.bold, fontSize: 11 },
  modalRoot: { flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(15, 23, 42, 0.48)' },
  pickerCard: { maxHeight: '75%', borderTopLeftRadius: 24, borderTopRightRadius: 24, padding: 20, paddingBottom: 28 },
  pickerTitle: { fontFamily: Typography.fontFamily.bold, fontSize: 17, marginBottom: 12 },
  pickerOption: { minHeight: 52, borderBottomWidth: StyleSheet.hairlineWidth, flexDirection: 'row', alignItems: 'center', paddingVertical: 9 },
  pickerOptionCopy: { flex: 1 },
  pickerOptionLabel: { fontFamily: Typography.fontFamily.semiBold, fontSize: 13 },
  pickerOptionDetail: { fontFamily: Typography.fontFamily.regular, fontSize: 10, marginTop: 3 },
});

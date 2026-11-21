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
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ArrowRight, Bell, CheckCircle, CircleDashed, Star } from 'lucide-react-native';
import { useRouter } from 'expo-router';
import { useAuth } from '../../src/context/AuthContext';
import { useTheme } from '../../src/context/ThemeContext';
import { Radius, Spacing, Typography } from '../../src/constants/theme';
import { EmptyState } from '../../src/components/EmptyState';
import { Toast } from '../../src/components/Toast';
import { api, getErrorMessage } from '../../src/services/api';

interface ScheduleEvaluation {
  schedule_id: number | null;
  evaluation_id: number | null;
  class: string;
  period: string;
  start: string;
  end: string;
  subject: string;
  teacher: string;
  rating: string | null;
  rating_label: string | null;
  note: string | null;
  created_at: string | null;
}

interface TodayResponse {
  server_time: string;
  holiday: { name: string; date: string } | null;
  data: ScheduleEvaluation[];
}

interface Reminder {
  id: number;
  title: string;
  message: string;
  period?: string | null;
  start?: string | null;
  end?: string | null;
  sender?: string | null;
  read: boolean;
}

export default function HomeScreen() {
  const { user } = useAuth();
  const { colors } = useTheme();
  const router = useRouter();

  const [schedules, setSchedules] = useState<ScheduleEvaluation[]>([]);
  const [serverTime, setServerTime] = useState(new Date().toISOString());
  const [holiday, setHoliday] = useState<TodayResponse['holiday']>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [reminders, setReminders] = useState<Reminder[]>([]);
  const [activeReminder, setActiveReminder] = useState<Reminder | null>(null);
  const [toastMessage, setToastMessage] = useState('');
  const [toastType, setToastType] = useState<'success' | 'error'>('success');
  const [toastVisible, setToastVisible] = useState(false);

  const showToast = (message: string, type: 'success' | 'error') => {
    setToastMessage(message);
    setToastType(type);
    setToastVisible(true);
  };

  const fetchToday = useCallback(async (manualRefresh = false) => {
    if (manualRefresh) setRefreshing(true);

    try {
      const response = await api.get<TodayResponse>('/teacher-ratings/today');
      setSchedules(response.data.data);
      setServerTime(response.data.server_time);
      setHoliday(response.data.holiday);
    } catch (error) {
      showToast(getErrorMessage(error), 'error');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  const fetchReminders = useCallback(async () => {
    try {
      const response = await api.get('/notifications');
      const items: Reminder[] = response.data.data ?? [];
      setReminders(items);
      setActiveReminder((current) => current ?? items.find((item) => !item.read) ?? null);
    } catch (error) {
      console.error('Gagal mengambil pengingat admin:', error);
    }
  }, []);

  useEffect(() => {
    void fetchToday();
    void fetchReminders();

    const interval = setInterval(() => {
      void fetchToday();
      void fetchReminders();
    }, 60000);
    const subscription = AppState.addEventListener('change', (state) => {
      if (state === 'active') {
        void fetchToday();
        void fetchReminders();
      }
    });

    return () => {
      clearInterval(interval);
      subscription.remove();
    };
  }, [fetchReminders, fetchToday]);

  const stats = useMemo(() => {
    const filled = schedules.filter((item) => item.rating !== null).length;
    return {
      total: schedules.length,
      filled,
      pending: schedules.length - filled,
      percent: schedules.length ? Math.round((filled / schedules.length) * 100) : 0,
    };
  }, [schedules]);

  const activities = useMemo(
    () => schedules
      .filter((item) => item.rating !== null)
      .sort((a, b) => (b.created_at ?? '').localeCompare(a.created_at ?? ''))
      .slice(0, 5),
    [schedules]
  );

  const formattedDate = useMemo(
    () => new Date(serverTime).toLocaleDateString('id-ID', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }),
    [serverTime]
  );

  const markReminderRead = async (reminder: Reminder) => {
    try {
      await api.post(`/notifications/${reminder.id}/read`);
      setReminders((current) => current.map((item) => (
        item.id === reminder.id ? { ...item, read: true } : item
      )));
      const nextReminder = reminders.find((item) => item.id !== reminder.id && !item.read) ?? null;
      setActiveReminder(nextReminder);
    } catch (error) {
      showToast(getErrorMessage(error), 'error');
    }
  };

  const progressWidth = `${stats.percent}%` as `${number}%`;

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
            onRefresh={() => void Promise.all([fetchToday(true), fetchReminders()])}
            tintColor={colors.primary}
            colors={[colors.primary]}
          />
        }
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.headerTop}>
          <View>
            <Text style={[styles.dateEyebrow, { color: colors.textMuted }]}>{formattedDate.toUpperCase()}</Text>
            <Text style={[styles.greeting, { color: colors.text }]}>Halo, {user?.name?.split(' ')[0]}! 👋</Text>
            <Text style={[styles.subtitle, { color: colors.textMuted }]}>Siap melakukan monitoring hari ini?</Text>
          </View>
          <TouchableOpacity
            onPress={() => setActiveReminder(reminders.find((item) => !item.read) ?? null)}
            style={[styles.bellButton, { backgroundColor: colors.surface }]}
            accessibilityLabel="Lihat pengingat"
          >
            <Bell size={20} color={colors.primary} />
            {reminders.some((item) => !item.read) && <View style={styles.unreadDot} />}
          </TouchableOpacity>
        </View>

        <View style={[styles.summaryCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
          <View style={styles.summaryHeader}>
            <Text style={[styles.summaryTitle, { color: colors.text }]}>Monitoring Guru</Text>
            <View style={styles.todayPill}>
              <Text style={styles.todayPillText}>Hari ini</Text>
            </View>
          </View>
          {loading ? (
            <ActivityIndicator style={styles.summaryLoader} color={colors.primary} />
          ) : (
            <>
              <Text style={[styles.summaryNumber, { color: colors.text }]}>{stats.filled}</Text>
              <Text style={[styles.summaryCaption, { color: colors.textSecondary }]}>
                Total penilaian yang sudah diinput hari ini
              </Text>
              <View style={[styles.progressTrack, { backgroundColor: colors.border }]}>
                <View style={[styles.progressBar, { width: progressWidth, backgroundColor: colors.primary }]} />
              </View>
              <Text style={[styles.progressCaption, { color: colors.textMuted }]}>
                {stats.filled} dari {stats.total} penilaian tercatat hari ini
              </Text>
            </>
          )}
        </View>

        <View style={styles.statsRow}>
          <View style={[styles.statCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <CheckCircle size={19} color="#059669" />
            <Text style={[styles.statNumber, { color: colors.text }]}>{stats.filled}</Text>
            <Text style={[styles.statLabel, { color: colors.textMuted }]}>Sudah Diisi Hari Ini</Text>
          </View>
          <View style={[styles.statCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <CircleDashed size={19} color="#d97706" />
            <Text style={[styles.statNumber, { color: colors.text }]}>{stats.pending}</Text>
            <Text style={[styles.statLabel, { color: colors.textMuted }]}>Belum Diisi</Text>
          </View>
        </View>

        {holiday && (
          <View style={[styles.holidayCard, { backgroundColor: colors.surface }]}>
            <Text style={[styles.holidayTitle, { color: colors.text }]}>Hari ini libur sekolah</Text>
            <Text style={[styles.holidayText, { color: colors.textMuted }]}>{holiday.name}</Text>
          </View>
        )}

        <TouchableOpacity
          activeOpacity={0.88}
          onPress={() => router.push('/(tabs)/rating')}
          style={[styles.startButton, { backgroundColor: colors.primary }]}
        >
          <View style={styles.startCopy}>
            <Text style={styles.startTitle}>Mulai Monitoring</Text>
            <Text style={styles.startSubtitle}>Isi penilaian guru untuk jadwal hari ini</Text>
          </View>
          <ArrowRight size={20} color="#ffffff" />
        </TouchableOpacity>

        <View style={styles.activityHeader}>
          <Text style={[styles.activityTitle, { color: colors.text }]}>Aktivitas Terbaru</Text>
          {activities.length > 0 && (
            <TouchableOpacity onPress={() => router.push('/(tabs)/history')}>
              <Text style={[styles.seeAllText, { color: colors.primary }]}>Lihat riwayat</Text>
            </TouchableOpacity>
          )}
        </View>

        {loading ? (
          <ActivityIndicator style={styles.activityLoader} color={colors.primary} />
        ) : activities.length === 0 ? (
          <EmptyState
            title="Belum Ada Penilaian Hari Ini"
            message="Penilaian yang sudah diinput hari ini akan muncul di sini."
          />
        ) : (
          activities.map((item) => (
            <View key={item.evaluation_id ?? item.schedule_id} style={[styles.activityRow, { borderBottomColor: colors.border }]}>
              <View style={styles.activityIcon}>
                <Star size={15} color={colors.primary} />
              </View>
              <View style={styles.activityCopy}>
                <Text style={[styles.activityClass, { color: colors.text }]}>{item.class}</Text>
                <Text style={[styles.activityDescription, { color: colors.textMuted }]}>
                  {item.subject} · {item.teacher}
                </Text>
              </View>
              <Text style={[styles.activityRating, { color: item.rating === 'kurang' ? '#dc2626' : '#059669' }]}>
                {item.rating_label}
              </Text>
            </View>
          ))
        )}
      </ScrollView>

      <Modal
        visible={activeReminder !== null}
        transparent
        animationType="fade"
        onRequestClose={() => {
          if (activeReminder) void markReminderRead(activeReminder);
        }}
      >
        <View style={styles.modalRoot}>
          <Pressable
            style={StyleSheet.absoluteFill}
            onPress={() => {
              if (activeReminder) void markReminderRead(activeReminder);
            }}
          />
          {activeReminder && (
            <View style={[styles.reminderCard, { backgroundColor: colors.surface }]}>
              <View style={styles.reminderHeader}>
                <View style={[styles.reminderIcon, { backgroundColor: colors.primarySoft }]}>
                  <Bell size={21} color={colors.primary} />
                </View>
                <View style={styles.reminderCopy}>
                  <Text style={[styles.reminderTitle, { color: colors.text }]}>{activeReminder.title}</Text>
                  <Text style={[styles.reminderSender, { color: colors.textMuted }]}>
                    Pengingat dari Admin
                  </Text>
                </View>
              </View>
              <Text style={[styles.reminderMessage, { color: colors.textSecondary }]}>
                {activeReminder.message}
              </Text>
              {activeReminder.period && (
                <Text style={[styles.reminderPeriod, { color: colors.primary }]}>
                  {activeReminder.period}
                  {activeReminder.start ? ` · ${activeReminder.start}-${activeReminder.end}` : ''}
                </Text>
              )}
              <TouchableOpacity
                onPress={() => void markReminderRead(activeReminder)}
                style={[styles.reminderButton, { backgroundColor: colors.primary }]}
              >
                <Text style={styles.reminderButtonText}>Mengerti</Text>
              </TouchableOpacity>
            </View>
          )}
        </View>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  content: { padding: Spacing.lg, paddingBottom: 100 },
  headerTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 },
  dateEyebrow: { fontFamily: Typography.fontFamily.medium, fontSize: 10, letterSpacing: 0.3 },
  greeting: { fontFamily: Typography.fontFamily.bold, fontSize: 21, marginTop: 6 },
  subtitle: { fontFamily: Typography.fontFamily.regular, fontSize: 12, marginTop: 3 },
  bellButton: { width: 44, height: 44, borderRadius: 22, alignItems: 'center', justifyContent: 'center' },
  unreadDot: { position: 'absolute', top: 8, right: 9, width: 9, height: 9, borderRadius: 5, backgroundColor: '#ef4444' },
  summaryCard: { borderWidth: 1, borderRadius: Radius.lg, padding: 14, marginBottom: 10 },
  summaryHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  summaryTitle: { fontFamily: Typography.fontFamily.bold, fontSize: 15 },
  todayPill: { backgroundColor: '#dcfce7', borderRadius: 12, paddingHorizontal: 9, paddingVertical: 4 },
  todayPillText: { color: '#059669', fontFamily: Typography.fontFamily.bold, fontSize: 10 },
  summaryLoader: { marginVertical: 24 },
  summaryNumber: { fontFamily: Typography.fontFamily.bold, fontSize: 26, marginTop: 12 },
  summaryCaption: { fontFamily: Typography.fontFamily.medium, fontSize: 11, marginTop: 3 },
  progressTrack: { height: 6, borderRadius: 4, overflow: 'hidden', marginTop: 10 },
  progressBar: { height: '100%', borderRadius: 4 },
  progressCaption: { fontFamily: Typography.fontFamily.regular, fontSize: 10, marginTop: 7 },
  statsRow: { flexDirection: 'row', gap: 10, marginBottom: 12 },
  statCard: { flex: 1, minHeight: 112, borderWidth: 1, borderRadius: Radius.lg, padding: 12 },
  statNumber: { fontFamily: Typography.fontFamily.bold, fontSize: 21, marginTop: 8 },
  statLabel: { fontFamily: Typography.fontFamily.medium, fontSize: 10, marginTop: 2 },
  holidayCard: { borderRadius: Radius.lg, padding: 12, marginBottom: 12 },
  holidayTitle: { fontFamily: Typography.fontFamily.bold, fontSize: 13 },
  holidayText: { fontFamily: Typography.fontFamily.medium, fontSize: 11, marginTop: 3 },
  startButton: { minHeight: 62, borderRadius: Radius.lg, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 15, marginBottom: 20 },
  startCopy: { flex: 1 },
  startTitle: { color: '#ffffff', fontFamily: Typography.fontFamily.bold, fontSize: 14 },
  startSubtitle: { color: 'rgba(255,255,255,0.85)', fontFamily: Typography.fontFamily.medium, fontSize: 10, marginTop: 3 },
  activityHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 5 },
  activityTitle: { fontFamily: Typography.fontFamily.bold, fontSize: 16 },
  seeAllText: { fontFamily: Typography.fontFamily.bold, fontSize: 11 },
  activityLoader: { marginVertical: 24 },
  activityRow: { minHeight: 62, borderBottomWidth: StyleSheet.hairlineWidth, flexDirection: 'row', alignItems: 'center', paddingVertical: 9 },
  activityIcon: { width: 32, height: 32, borderRadius: 11, backgroundColor: '#eff6ff', alignItems: 'center', justifyContent: 'center', marginRight: 9 },
  activityCopy: { flex: 1 },
  activityClass: { fontFamily: Typography.fontFamily.bold, fontSize: 12 },
  activityDescription: { fontFamily: Typography.fontFamily.regular, fontSize: 10, marginTop: 3 },
  activityRating: { fontFamily: Typography.fontFamily.bold, fontSize: 10, marginLeft: 6 },
  modalRoot: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: 'rgba(15, 23, 42, 0.48)', paddingHorizontal: 22 },
  reminderCard: { width: '100%', maxWidth: 390, borderRadius: 22, padding: 18 },
  reminderHeader: { flexDirection: 'row', alignItems: 'center' },
  reminderIcon: { width: 44, height: 44, borderRadius: 14, alignItems: 'center', justifyContent: 'center' },
  reminderCopy: { flex: 1, marginLeft: 10 },
  reminderTitle: { fontFamily: Typography.fontFamily.bold, fontSize: 14 },
  reminderSender: { fontFamily: Typography.fontFamily.medium, fontSize: 10, marginTop: 3 },
  reminderMessage: { fontFamily: Typography.fontFamily.medium, fontSize: 12, lineHeight: 19, marginTop: 14 },
  reminderPeriod: { fontFamily: Typography.fontFamily.bold, fontSize: 11, marginTop: 10 },
  reminderButton: { height: 44, borderRadius: 13, alignItems: 'center', justifyContent: 'center', marginTop: 16 },
  reminderButtonText: { color: '#ffffff', fontFamily: Typography.fontFamily.bold, fontSize: 12 },
});

import React, { useState, useEffect, useRef } from 'react';
import {
  View,
  Text,
  FlatList,
  RefreshControl,
  StyleSheet,
  AppState,
  Platform,
  StatusBar,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import * as Haptics from 'expo-haptics';
import { WifiOff, Clock } from 'lucide-react-native';
import { useAuth } from '../../src/context/AuthContext';
import { useTheme } from '../../src/context/ThemeContext';
import { Spacing, Typography, Radius } from '../../src/constants/theme';
import { SlotCard, SlotData } from '../../src/components/SlotCard';
import { ActionBottomSheet } from '../../src/components/ActionBottomSheet';
import { EmptyState } from '../../src/components/EmptyState';
import { SkeletonCard } from '../../src/components/Skeleton';
import { Toast } from '../../src/components/Toast';
import { api, getErrorMessage } from '../../src/services/api';

export default function TodayScreen() {
  const { user, classes } = useAuth();
  const { colors } = useTheme();

  const [slots, setSlots] = useState<SlotData[]>([]);
  const [serverTimeStr, setServerTimeStr] = useState<string>(new Date().toISOString());
  const [holiday, setHoliday] = useState<{ name: string; date: string } | null>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const [refreshing, setRefreshing] = useState<boolean>(false);
  const [isOffline, setIsOffline] = useState<boolean>(false);

  // Modal Sheet State
  const [selectedSlotForAction, setSelectedSlotForAction] = useState<SlotData | null>(null);
  const [isSheetVisible, setIsSheetVisible] = useState<boolean>(false);
  const [isSubmitting, setIsSubmitting] = useState<boolean>(false);

  // Toast State
  const [toastMessage, setToastMessage] = useState<string>('');
  const [toastType, setToastType] = useState<'success' | 'error'>('success');
  const [isToastVisible, setIsToastVisible] = useState<boolean>(false);

  // Server clock ticking
  const [serverClock, setServerClock] = useState<Date>(new Date());
  const flatListRef = useRef<FlatList>(null);

  const fetchTodayData = async (isManualRefresh = false) => {
    if (isManualRefresh) setRefreshing(true);
    try {
      const response = await api.get('/attendance/today');
      const { data, server_time, holiday: hol } = response.data;

      setSlots(data);
      setServerTimeStr(server_time);
      setServerClock(new Date(server_time));
      setHoliday(hol);
      setIsOffline(false);
    } catch (error: any) {
      if (!error.response) {
        setIsOffline(true);
      }
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchTodayData();

    // Auto-refresh tiap 60 detik
    const interval = setInterval(() => {
      fetchTodayData();
    }, 60000);

    // Refresh saat aplikasi kembali aktif
    const sub = AppState.addEventListener('change', (nextState) => {
      if (nextState === 'active') {
        fetchTodayData();
      }
    });

    return () => {
      clearInterval(interval);
      sub.remove();
    };
  }, []);

  // Jam server berdetak tiap detik
  useEffect(() => {
    const clockInterval = setInterval(() => {
      setServerClock((prev) => new Date(prev.getTime() + 1000));
    }, 1000);

    return () => clearInterval(clockInterval);
  }, []);

  const showToast = (message: string, type: 'success' | 'error' = 'success') => {
    setToastMessage(message);
    setToastType(type);
    setIsToastVisible(true);
  };

  // Handler tombol "Guru Datang"
  const handlePressDatang = async (slot: SlotData) => {
    setIsSubmitting(true);
    try {
      const res = await api.post('/attendance', {
        schedule_id: slot.schedule_id,
        action: 'datang',
      });

      const att = res.data.data;
      Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success).catch(() => {});

      const statusDesc =
        att.status === 'hadir'
          ? 'Tercatat: Hadir Tepat Waktu'
          : `Tercatat: Terlambat ${att.late_minutes} Menit`;

      showToast(statusDesc, 'success');
      await fetchTodayData();
    } catch (error: any) {
      Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error).catch(() => {});
      showToast(getErrorMessage(error), 'error');
    } finally {
      setIsSubmitting(false);
    }
  };

  // Handler aksi dari BottomSheet (kosong, izin, sakit, dinas)
  const handleSheetSubmit = async (action: string, note?: string) => {
    if (!selectedSlotForAction) return;

    setIsSubmitting(true);
    try {
      await api.post('/attendance', {
        schedule_id: selectedSlotForAction.schedule_id,
        action,
        note,
      });

      Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success).catch(() => {});
      setIsSheetVisible(false);
      showToast(`Tercatat: ${action.toUpperCase()}`, 'success');
      await fetchTodayData();
    } catch (error: any) {
      Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error).catch(() => {});
      showToast(getErrorMessage(error), 'error');
    } finally {
      setIsSubmitting(false);
    }
  };

  // Format tanggal Indonesia
  const formatDateIndonesia = (d: Date) => {
    const hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const bulan = [
      'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    return `${hari[d.getDay()]}, ${d.getDate()} ${bulan[d.getMonth()]} ${d.getFullYear()}`;
  };

  const timeString = `${String(serverClock.getHours()).padStart(2, '0')}:${String(
    serverClock.getMinutes()
  ).padStart(2, '0')}:${String(serverClock.getSeconds()).padStart(2, '0')}`;

  const classNames = classes.map((c) => c.name).join(', ') || 'Kelas Anda';

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: colors.bg }]}>
      {/* Toast Feedback */}
      <Toast
        visible={isToastVisible}
        message={toastMessage}
        type={toastType}
        onDismiss={() => setIsToastVisible(false)}
      />

      {/* Offline Banner */}
      {isOffline && (
        <View style={[styles.offlineBanner, { backgroundColor: colors.danger }]}>
          <WifiOff size={16} color="#FFFFFF" style={{ marginRight: 6 }} />
          <Text style={styles.offlineText}>
            Tidak ada koneksi ke server. Tombol absensi dinonaktifkan.
          </Text>
        </View>
      )}

      {/* Header Beranda */}
      <View style={[styles.header, { backgroundColor: colors.surface, borderBottomColor: colors.border }]}>
        <View style={styles.headerTop}>
          <View>
            <Text style={[styles.greeting, { color: colors.textMuted }]}>
              Halo, {user?.name?.split(' ')[0]} 👋
            </Text>
            <Text style={[styles.dateText, { color: colors.text }]}>
              {formatDateIndonesia(serverClock)}
            </Text>
          </View>

          {/* Jam Server Berdetak */}
          <View style={[styles.clockBadge, { backgroundColor: colors.primarySoft }]}>
            <Clock size={14} color={colors.primary} style={{ marginRight: 4 }} />
            <Text style={[styles.clockText, { color: colors.primary }]}>{timeString}</Text>
          </View>
        </View>

        {/* Chip Kelas */}
        <View style={styles.headerBottom}>
          <View style={[styles.classChip, { backgroundColor: colors.bg, borderColor: colors.border }]}>
            <Text style={[styles.classChipText, { color: colors.text }]}>
              {classNames}
            </Text>
          </View>
        </View>
      </View>

      {/* Content List */}
      <View style={styles.body}>
        {loading ? (
          <View style={{ padding: Spacing.lg }}>
            <SkeletonCard />
            <SkeletonCard />
            <SkeletonCard />
          </View>
        ) : holiday ? (
          <EmptyState
            title="Hari Ini Libur Sekolah"
            message={`${holiday.name}. Tidak ada kegiatan belajar mengajar atau pencatatan absensi.`}
          />
        ) : slots.length === 0 ? (
          <EmptyState
            title="Tidak Ada Jadwal Hari Ini"
            message="Tidak ada jadwal mata pelajaran untuk kelas Anda pada hari ini."
          />
        ) : (
          <FlatList
            ref={flatListRef}
            data={slots}
            keyExtractor={(item) => String(item.schedule_id)}
            contentContainerStyle={styles.listContent}
            refreshControl={
              <RefreshControl
                refreshing={refreshing}
                onRefresh={() => fetchTodayData(true)}
                tintColor={colors.primary}
                colors={[colors.primary]}
              />
            }
            renderItem={({ item }) => (
              <SlotCard
                slot={item}
                serverTime={serverTimeStr}
                isActionLoading={isSubmitting || isOffline}
                onPressDatang={handlePressDatang}
                onPressLainnya={(slot) => {
                  setSelectedSlotForAction(slot);
                  setIsSheetVisible(true);
                }}
                onPressEdit={(slot) => {
                  setSelectedSlotForAction(slot);
                  setIsSheetVisible(true);
                }}
              />
            )}
          />
        )}
      </View>

      {/* BottomSheet Form Aksi */}
      <ActionBottomSheet
        visible={isSheetVisible}
        slot={selectedSlotForAction}
        serverTime={serverTimeStr}
        loading={isSubmitting}
        onClose={() => setIsSheetVisible(false)}
        onSubmit={handleSheetSubmit}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight : 0,
  },
  offlineBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 8,
    paddingHorizontal: 16,
  },
  offlineText: {
    color: '#FFFFFF',
    fontFamily: Typography.fontFamily.semiBold,
    fontSize: 12,
  },
  header: {
    paddingHorizontal: Spacing.lg,
    paddingTop: Spacing.md,
    paddingBottom: Spacing.md,
    borderBottomWidth: 1,
  },
  headerTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  greeting: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 13,
  },
  dateText: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 16,
    marginTop: 2,
  },
  clockBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: Radius.chip,
  },
  clockText: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 14,
    fontVariant: ['tabular-nums'],
  },
  headerBottom: {
    marginTop: Spacing.sm,
  },
  classChip: {
    alignSelf: 'flex-start',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: Radius.chip,
    borderWidth: 1,
  },
  classChipText: {
    fontFamily: Typography.fontFamily.semiBold,
    fontSize: 12,
  },
  body: {
    flex: 1,
  },
  listContent: {
    padding: Spacing.lg,
    paddingBottom: 40,
  },
});


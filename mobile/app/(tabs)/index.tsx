import React, { useState, useEffect, useRef, useMemo } from 'react';
import {
  View,
  Text,
  FlatList,
  RefreshControl,
  StyleSheet,
  AppState,
  Platform,
  StatusBar,
  TouchableOpacity,
  Image,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import * as Haptics from 'expo-haptics';
import { WifiOff, Clock, Sparkles, BookOpen, CheckCircle, AlertTriangle, XCircle } from 'lucide-react-native';
import { useAuth } from '../../src/context/AuthContext';
import { useTheme } from '../../src/context/ThemeContext';
import { Spacing, Typography, Radius } from '../../src/constants/theme';
import { SlotCard, SlotData } from '../../src/components/SlotCard';
import { ActionBottomSheet } from '../../src/components/ActionBottomSheet';
import { EmptyState } from '../../src/components/EmptyState';
import { SkeletonCard } from '../../src/components/Skeleton';
import { Toast } from '../../src/components/Toast';
import { api, getErrorMessage } from '../../src/services/api';

type FilterType = 'all' | 'ongoing' | 'pending' | 'done';

export default function TodayScreen() {
  const { user, classes } = useAuth();
  const { colors } = useTheme();

  const [slots, setSlots] = useState<SlotData[]>([]);
  const [serverTimeStr, setServerTimeStr] = useState<string>(new Date().toISOString());
  const [holiday, setHoliday] = useState<{ name: string; date: string } | null>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const [refreshing, setRefreshing] = useState<boolean>(false);
  const [isOffline, setIsOffline] = useState<boolean>(false);
  const [activeFilter, setActiveFilter] = useState<FilterType>('all');

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
      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];
    return `${hari[d.getDay()]}, ${d.getDate()} ${bulan[d.getMonth()]} ${d.getFullYear()}`;
  };

  const timeString = `${String(serverClock.getHours()).padStart(2, '0')}:${String(
    serverClock.getMinutes()
  ).padStart(2, '0')}:${String(serverClock.getSeconds()).padStart(2, '0')}`;

  const classNames = classes.map((c) => c.name).join(', ') || 'Kelas Anda';

  // Kalkulasi statistik cepat hari ini
  const stats = useMemo(() => {
    const total = slots.length;
    let hadir = 0;
    let terlambat = 0;
    let kosong = 0;

    slots.forEach((s) => {
      if (s.attendance) {
        if (s.attendance.status === 'hadir') hadir++;
        else if (s.attendance.status === 'terlambat') terlambat++;
        else if (s.attendance.status === 'kosong') kosong++;
      }
    });

    return { total, hadir, terlambat, kosong };
  }, [slots]);

  // Filter slots
  const filteredSlots = useMemo(() => {
    if (activeFilter === 'all') return slots;

    const currentMinutes = serverClock.getHours() * 60 + serverClock.getMinutes();

    return slots.filter((slot) => {
      const [startH, startM] = slot.start.split(':').map(Number);
      const [endH, endM] = slot.end.split(':').map(Number);
      const startMinutes = startH * 60 + startM;
      const endMinutes = endH * 60 + endM;

      const isOngoing = currentMinutes >= startMinutes && currentMinutes <= endMinutes;
      const hasAttendance = slot.attendance !== null;

      if (activeFilter === 'ongoing') {
        return isOngoing && !hasAttendance;
      }
      if (activeFilter === 'pending') {
        return !hasAttendance;
      }
      if (activeFilter === 'done') {
        return hasAttendance;
      }
      return true;
    });
  }, [slots, activeFilter, serverClock]);

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: colors.bg }]}>
      <StatusBar barStyle="dark-content" />

      {/* Toast Feedback */}
      <Toast
        visible={isToastVisible}
        message={toastMessage}
        type={toastType}
        onDismiss={() => setIsToastVisible(false)}
      />

      {/* Offline Banner */}
      {isOffline && (
        <View style={styles.offlineBanner}>
          <WifiOff size={15} color="#ffffff" style={{ marginRight: 6 }} />
          <Text style={styles.offlineText}>
            Mode Offline: Gagal terhubung ke server. Tombol absensi dinonaktifkan.
          </Text>
        </View>
      )}

      {/* Header Utama Modern */}
      <View style={styles.header}>
        {/* Row 1: Logo Tutwuri & Nama Sekolah & Live Sync Clock */}
        <View style={styles.headerTop}>
          <View style={styles.schoolRow}>
            <Image
              source={require('../../assets/logo-tutwuri.png')}
              style={styles.schoolLogo}
              resizeMode="contain"
            />
            <View>
              <Text style={styles.schoolTitle}>SMKN 1 GUNUNGPUTRI</Text>
              <Text style={styles.schoolSubtitle}>Sistem Absensi Guru Digital</Text>
            </View>
          </View>

          {/* Jam Digital Server dengan Indikator LIVE */}
          <View style={styles.liveClockBadge}>
            <View style={styles.liveDot} />
            <Text style={styles.liveClockText}>{timeString} WIB</Text>
          </View>
        </View>

        {/* Row 2: Greeting & Assigned Class Pill */}
        <View style={styles.greetingRow}>
          <View>
            <Text style={styles.greetingText}>
              Halo, {user?.name?.split(' ')[0]} 👋
            </Text>
            <Text style={styles.dateSubtext}>{formatDateIndonesia(serverClock)}</Text>
          </View>

          <View style={styles.classPill}>
            <BookOpen size={12} color="#0056b3" style={{ marginRight: 4 }} />
            <Text style={styles.classPillText}>{classNames}</Text>
          </View>
        </View>

        {/* Row 3: Ringkasan Status Kehadiran Hari Ini (KPI Cards) */}
        <View style={styles.statsRow}>
          <View style={styles.statCard}>
            <Text style={styles.statNumber}>{stats.total}</Text>
            <Text style={styles.statLabel}>Total Jam</Text>
          </View>

          <View style={styles.statCard}>
            <Text style={[styles.statNumber, { color: '#059669' }]}>{stats.hadir}</Text>
            <Text style={styles.statLabel}>Hadir</Text>
          </View>

          <View style={styles.statCard}>
            <Text style={[styles.statNumber, { color: '#d97706' }]}>{stats.terlambat}</Text>
            <Text style={styles.statLabel}>Terlambat</Text>
          </View>

          <View style={styles.statCard}>
            <Text style={[styles.statNumber, { color: '#dc2626' }]}>{stats.kosong}</Text>
            <Text style={styles.statLabel}>Kosong</Text>
          </View>
        </View>

        {/* Row 4: Filter Segment Chips */}
        <View style={styles.filterBar}>
          <TouchableOpacity
            onPress={() => setActiveFilter('all')}
            style={[styles.filterChip, activeFilter === 'all' && styles.filterChipActive]}
          >
            <Text style={[styles.filterChipText, activeFilter === 'all' && styles.filterChipTextActive]}>
              Semua ({slots.length})
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            onPress={() => setActiveFilter('ongoing')}
            style={[styles.filterChip, activeFilter === 'ongoing' && styles.filterChipActive]}
          >
            <Text style={[styles.filterChipText, activeFilter === 'ongoing' && styles.filterChipTextActive]}>
              Sedang Berlangsung
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            onPress={() => setActiveFilter('pending')}
            style={[styles.filterChip, activeFilter === 'pending' && styles.filterChipActive]}
          >
            <Text style={[styles.filterChipText, activeFilter === 'pending' && styles.filterChipTextActive]}>
              Belum Diabsen
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            onPress={() => setActiveFilter('done')}
            style={[styles.filterChip, activeFilter === 'done' && styles.filterChipActive]}
          >
            <Text style={[styles.filterChipText, activeFilter === 'done' && styles.filterChipTextActive]}>
              Selesai
            </Text>
          </TouchableOpacity>
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
        ) : filteredSlots.length === 0 ? (
          <EmptyState
            title={activeFilter === 'all' ? 'Tidak Ada Jadwal Hari Ini' : 'Tidak Ada Data'}
            message={
              activeFilter === 'all'
                ? 'Tidak ada jadwal mata pelajaran untuk kelas Anda pada hari ini.'
                : 'Tidak ada jadwal yang cocok dengan filter yang dipilih.'
            }
          />
        ) : (
          <FlatList
            ref={flatListRef}
            data={filteredSlots}
            keyExtractor={(item) => String(item.schedule_id)}
            contentContainerStyle={styles.listContent}
            showsVerticalScrollIndicator={false}
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
  },
  offlineBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 7,
    paddingHorizontal: 16,
    backgroundColor: '#dc2626',
  },
  offlineText: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 12,
    color: '#ffffff',
  },
  header: {
    backgroundColor: '#ffffff',
    paddingHorizontal: 20,
    paddingTop: 14,
    paddingBottom: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#f1f5f9',
    shadowColor: '#0f172a',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.03,
    shadowRadius: 6,
    elevation: 2,
  },
  headerTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  schoolRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  schoolLogo: {
    width: 32,
    height: 32,
    marginRight: 8,
  },
  schoolTitle: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 12,
    color: '#0056b3',
    letterSpacing: 0.8,
  },
  schoolSubtitle: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 10,
    color: '#64748b',
  },
  liveClockBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#eff6ff',
    paddingVertical: 4,
    paddingHorizontal: 8,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#bfdbfe',
  },
  liveDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: '#10b981',
    marginRight: 5,
  },
  liveClockText: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 11,
    color: '#0056b3',
  },
  greetingRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 14,
  },
  greetingText: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 18,
    color: '#0f172a',
  },
  dateSubtext: {
    fontFamily: Typography.fontFamily.regular,
    fontSize: 12,
    color: '#64748b',
    marginTop: 1,
  },
  classPill: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#eff6ff',
    paddingVertical: 5,
    paddingHorizontal: 10,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#bfdbfe',
  },
  classPillText: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 12,
    color: '#0056b3',
  },
  statsRow: {
    flexDirection: 'row',
    gap: 8,
    marginBottom: 12,
  },
  statCard: {
    flex: 1,
    backgroundColor: '#ffffff',
    borderRadius: 10,
    paddingVertical: 8,
    paddingHorizontal: 10,
    borderWidth: 1,
    borderColor: '#e2e8f0',
  },
  statNumber: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 16,
    color: '#0f172a',
  },
  statLabel: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 10,
    color: '#64748b',
    marginTop: 1,
  },
  filterBar: {
    flexDirection: 'row',
    gap: 6,
    overflow: 'hidden',
  },
  filterChip: {
    paddingVertical: 4.5,
    paddingHorizontal: 10,
    borderRadius: 20,
    backgroundColor: '#f1f5f9',
  },
  filterChipActive: {
    backgroundColor: '#0056b3',
  },
  filterChipText: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 11,
    color: '#64748b',
  },
  filterChipTextActive: {
    color: '#ffffff',
    fontFamily: Typography.fontFamily.bold,
  },
  body: {
    flex: 1,
  },
  listContent: {
    padding: Spacing.lg,
    paddingBottom: Spacing.xxl,
  },
});

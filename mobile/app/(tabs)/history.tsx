import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  RefreshControl,
  StyleSheet,
  Platform,
  StatusBar,
  ActivityIndicator,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Calendar, Filter } from 'lucide-react-native';
import { useTheme } from '../../src/context/ThemeContext';
import { Radius, Spacing, Typography } from '../../src/constants/theme';
import { StatusChip, StatusType } from '../../src/components/StatusChip';
import { EmptyState } from '../../src/components/EmptyState';
import { SkeletonCard } from '../../src/components/Skeleton';
import { api } from '../../src/services/api';

interface HistoryItem {
  id: number;
  date: string;
  status: StatusType;
  arrived_at: string | null;
  late_minutes: number;
  note: string | null;
  class: string;
  period: string;
  start: string;
  end: string;
  subject: string;
  teacher: string;
  recorded_by: string;
}

export default function HistoryScreen() {
  const { colors } = useTheme();

  const [items, setItems] = useState<HistoryItem[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [refreshing, setRefreshing] = useState<boolean>(false);
  const [loadingMore, setLoadingMore] = useState<boolean>(false);
  const [page, setPage] = useState<number>(1);
  const [lastPage, setLastPage] = useState<number>(1);

  // Filter chips: 'all' | 'terlambat' | 'kosong'
  const [activeFilter, setActiveFilter] = useState<'all' | 'terlambat' | 'kosong'>('all');

  const fetchHistory = async (pageNumber = 1, filter = activeFilter, isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    if (pageNumber > 1) setLoadingMore(true);

    try {
      let url = `/attendance/history?page=${pageNumber}`;
      if (filter !== 'all') {
        url += `&status=${filter}`;
      }

      const res = await api.get(url);
      const { data, last_page } = res.data;

      if (pageNumber === 1) {
        setItems(data);
      } else {
        setItems((prev) => [...prev, ...data]);
      }

      setPage(pageNumber);
      setLastPage(last_page);
    } catch (e) {
      console.error('Gagal mengambil data riwayat:', e);
    } finally {
      setLoading(false);
      setRefreshing(false);
      setLoadingMore(false);
    }
  };

  useEffect(() => {
    fetchHistory(1, activeFilter);
  }, [activeFilter]);

  const handleFilterChange = (filter: 'all' | 'terlambat' | 'kosong') => {
    setActiveFilter(filter);
    setLoading(true);
  };

  const handleLoadMore = () => {
    if (!loadingMore && page < lastPage) {
      fetchHistory(page + 1, activeFilter);
    }
  };

  const formatDateHeader = (dateStr: string) => {
    const d = new Date(dateStr);
    const hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const bulan = [
      'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    return `${hari[d.getDay()]}, ${d.getDate()} ${bulan[d.getMonth()]} ${d.getFullYear()}`;
  };

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: colors.bg }]}>
      {/* Header */}
      <View style={[styles.header, { backgroundColor: colors.surface, borderBottomColor: colors.border }]}>
        <Text style={[styles.headerTitle, { color: colors.text }]}>Riwayat Absensi</Text>
        <Text style={[styles.headerSubtitle, { color: colors.textMuted }]}>
          Catatan kehadiran guru kelas Anda
        </Text>

        {/* Filter Chips */}
        <View style={styles.filterRow}>
          <TouchableOpacity
            onPress={() => handleFilterChange('all')}
            style={[
              styles.filterChip,
              {
                backgroundColor: activeFilter === 'all' ? colors.primary : colors.bg,
                borderColor: activeFilter === 'all' ? colors.primary : colors.border,
              },
            ]}
          >
            <Text
              style={[
                styles.filterText,
                { color: activeFilter === 'all' ? '#FFFFFF' : colors.text },
              ]}
            >
              Semua
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            onPress={() => handleFilterChange('terlambat')}
            style={[
              styles.filterChip,
              {
                backgroundColor: activeFilter === 'terlambat' ? colors.warning : colors.bg,
                borderColor: activeFilter === 'terlambat' ? colors.warning : colors.border,
              },
            ]}
          >
            <Text
              style={[
                styles.filterText,
                { color: activeFilter === 'terlambat' ? '#FFFFFF' : colors.text },
              ]}
            >
              Terlambat
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            onPress={() => handleFilterChange('kosong')}
            style={[
              styles.filterChip,
              {
                backgroundColor: activeFilter === 'kosong' ? colors.danger : colors.bg,
                borderColor: activeFilter === 'kosong' ? colors.danger : colors.border,
              },
            ]}
          >
            <Text
              style={[
                styles.filterText,
                { color: activeFilter === 'kosong' ? '#FFFFFF' : colors.text },
              ]}
            >
              Kosong
            </Text>
          </TouchableOpacity>
        </View>
      </View>

      {/* List Content */}
      <View style={styles.body}>
        {loading ? (
          <View style={{ padding: Spacing.lg }}>
            <SkeletonCard />
            <SkeletonCard />
          </View>
        ) : items.length === 0 ? (
          <EmptyState
            title="Belum Ada Riwayat"
            message="Belum ada catatan absensi yang sesuai dengan filter ini."
          />
        ) : (
          <FlatList
            data={items}
            keyExtractor={(item) => String(item.id)}
            contentContainerStyle={styles.listContent}
            refreshControl={
              <RefreshControl
                refreshing={refreshing}
                onRefresh={() => fetchHistory(1, activeFilter, true)}
                tintColor={colors.primary}
                colors={[colors.primary]}
              />
            }
            onEndReached={handleLoadMore}
            onEndReachedThreshold={0.3}
            ListFooterComponent={
              loadingMore ? (
                <View style={{ paddingVertical: 16 }}>
                  <ActivityIndicator size="small" color={colors.primary} />
                </View>
              ) : undefined
            }
            renderItem={({ item, index }) => {
              const prevItem = items[index - 1];
              const showDateHeader = !prevItem || prevItem.date !== item.date;

              return (
                <View>
                  {showDateHeader && (
                    <View style={styles.dateHeaderRow}>
                      <Calendar size={13} color={colors.primary} style={{ marginRight: 6 }} />
                      <Text style={[styles.dateHeaderText, { color: colors.text }]}>
                        {formatDateHeader(item.date)}
                      </Text>
                    </View>
                  )}

                  <View
                    style={[
                      styles.historyCard,
                      { backgroundColor: colors.surface, borderColor: colors.border },
                    ]}
                  >
                    <View style={styles.cardHeaderRow}>
                      <View style={styles.timeTag}>
                        <Text style={[styles.timeTagText, { color: colors.textMuted }]}>
                          {item.start} - {item.end} ({item.period})
                        </Text>
                      </View>
                      <StatusChip status={item.status} lateMinutes={item.late_minutes} />
                    </View>

                    <Text style={[styles.subjectText, { color: colors.text }]}>
                      {item.subject}
                    </Text>

                    <Text style={[styles.teacherText, { color: colors.textMuted }]}>
                      Guru: {item.teacher}
                    </Text>

                    {item.note && (
                      <View style={[styles.noteBox, { backgroundColor: colors.bg }]}>
                        <Text style={[styles.noteText, { color: colors.textMuted }]}>
                          💬 Catatan: "{item.note}"
                        </Text>
                      </View>
                    )}

                    <View style={styles.cardFooter}>
                      <Text style={[styles.footerRecorded, { color: colors.textMuted }]}>
                        Dicatat oleh: {item.recorded_by}
                      </Text>
                    </View>
                  </View>
                </View>
              );
            }}
          />
        )}
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight : 0,
  },
  header: {
    paddingHorizontal: Spacing.lg,
    paddingTop: Spacing.md,
    paddingBottom: Spacing.sm,
    borderBottomWidth: 1,
  },
  headerTitle: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 20,
  },
  headerSubtitle: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 12,
    marginTop: 2,
  },
  filterRow: {
    flexDirection: 'row',
    gap: 8,
    marginTop: Spacing.md,
    marginBottom: Spacing.xs,
  },
  filterChip: {
    paddingHorizontal: 14,
    paddingVertical: 6,
    borderRadius: Radius.chip,
    borderWidth: 1,
  },
  filterText: {
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
  dateHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: Spacing.md,
    marginBottom: Spacing.sm,
  },
  dateHeaderText: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 13,
  },
  historyCard: {
    borderRadius: Radius.card,
    padding: Spacing.md,
    borderWidth: 1,
    marginBottom: Spacing.sm,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.04,
    shadowRadius: 4,
    elevation: 1,
  },
  cardHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 6,
  },
  timeTag: {},
  timeTagText: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 11,
  },
  subjectText: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 15,
  },
  teacherText: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 12,
    marginTop: 2,
  },
  noteBox: {
    marginTop: 8,
    padding: 8,
    borderRadius: 8,
  },
  noteText: {
    fontFamily: Typography.fontFamily.regular,
    fontSize: 11,
    fontStyle: 'italic',
  },
  cardFooter: {
    marginTop: 8,
    borderTopWidth: 1,
    borderTopColor: 'rgba(0,0,0,0.04)',
    paddingTop: 6,
  },
  footerRecorded: {
    fontFamily: Typography.fontFamily.regular,
    fontSize: 10,
  },
});

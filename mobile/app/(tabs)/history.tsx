import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  RefreshControl,
  StyleSheet,
  ActivityIndicator,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Calendar, Clock, User, MessageSquare } from 'lucide-react-native';
import { useTheme } from '../../src/context/ThemeContext';
import { Radius, Spacing, Typography } from '../../src/constants/theme';
import { StatusChip, StatusType } from '../../src/components/StatusChip';
import { EmptyState } from '../../src/components/EmptyState';
import { SkeletonCard } from '../../src/components/Skeleton';
import { api } from '../../src/services/api';

interface HistoryItem {
  id: number;
  date: string;
  type?: 'penilaian';
  status?: StatusType;
  rating_label?: string;
  arrived_at?: string | null;
  late_minutes?: number;
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
      let combinedData = data;
      let combinedLastPage = last_page;

      if (filter === 'all') {
        const ratingRes = await api.get(`/teacher-ratings/history?page=${pageNumber}`);
        combinedData = [...data, ...ratingRes.data.data].sort((a: HistoryItem, b: HistoryItem) => {
          const dateCompare = b.date.localeCompare(a.date);
          return dateCompare !== 0 ? dateCompare : b.id - a.id;
        });
        combinedLastPage = Math.max(last_page, ratingRes.data.last_page);
      }

      if (pageNumber === 1) {
        setItems(combinedData);
      } else {
        setItems((prev) => [...prev, ...combinedData]);
      }

      setPage(pageNumber);
      setLastPage(combinedLastPage);
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
      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];
    return `${hari[d.getDay()]}, ${d.getDate()} ${bulan[d.getMonth()]} ${d.getFullYear()}`;
  };

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: colors.bg }]}>
      {/* Header Elegan */}
      <View style={[styles.header, { backgroundColor: colors.surface, borderBottomColor: colors.border }]}>
        <View style={styles.aiLabel}>
          <View style={styles.aiDot} />
          <Text style={styles.aiLabelText}>SMART HISTORY</Text>
        </View>
        <Text style={[styles.headerTitle, { color: colors.text }]}>Riwayat Absensi</Text>
        <Text style={[styles.headerSubtitle, { color: colors.textMuted }]}>
          Catatan kehadiran guru kelas Anda dari hari ke hari
        </Text>

        {/* Filter Chips */}
        <View style={styles.filterRow}>
          <TouchableOpacity
            onPress={() => handleFilterChange('all')}
            style={[styles.filterChip, activeFilter === 'all' && styles.filterChipActive]}
          >
            <Text style={[styles.filterText, activeFilter === 'all' && styles.filterTextActive]}>
              Semua
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            onPress={() => handleFilterChange('terlambat')}
            style={[styles.filterChip, activeFilter === 'terlambat' && styles.filterChipActiveWarning]}
          >
            <Text style={[styles.filterText, activeFilter === 'terlambat' && styles.filterTextActive]}>
              Terlambat
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            onPress={() => handleFilterChange('kosong')}
            style={[styles.filterChip, activeFilter === 'kosong' && styles.filterChipActiveDanger]}
          >
            <Text style={[styles.filterText, activeFilter === 'kosong' && styles.filterTextActive]}>
              Guru Kosong
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
            showsVerticalScrollIndicator={false}
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
                      <Calendar size={13} color="#0056b3" style={{ marginRight: 6 }} />
                      <Text style={styles.dateHeaderText}>
                        {formatDateHeader(item.date)}
                      </Text>
                    </View>
                  )}

                  <View style={styles.historyCard}>
                    <View style={styles.cardInner}>
                      <View style={styles.cardHeaderRow}>
                        <View style={styles.timeTag}>
                          <Clock size={11} color="#0056b3" style={{ marginRight: 4 }} />
                          <Text style={styles.timeTagText}>
                            {item.start} - {item.end} ({item.period})
                          </Text>
                        </View>
                        {item.type === 'penilaian' ? (
                          <View style={styles.ratingTag}>
                            <Text style={styles.ratingTagText}>{item.rating_label}</Text>
                          </View>
                        ) : (
                          <StatusChip status={item.status as StatusType} lateMinutes={item.late_minutes ?? 0} />
                        )}
                      </View>

                      <Text style={styles.subjectText}>{item.subject}</Text>

                      <View style={styles.teacherRow}>
                        <User size={13} color="#64748b" style={{ marginRight: 5 }} />
                        <Text style={styles.teacherText}>{item.teacher}</Text>
                      </View>

                      {item.note && (
                        <View style={styles.noteBox}>
                          <MessageSquare size={12} color="#64748b" style={{ marginRight: 5, marginTop: 2 }} />
                          <Text style={styles.noteText}>"{item.note}"</Text>
                        </View>
                      )}

                      <View style={styles.cardFooter}>
                        <Text style={styles.footerRecorded}>
                          Dicatat oleh: <Text style={{ fontFamily: Typography.fontFamily.semiBold }}>{item.recorded_by}</Text>
                        </Text>
                        {item.arrived_at && (
                          <Text style={styles.footerArrived}>
                            Tiba: {item.arrived_at.substring(0, 5)} WIB
                          </Text>
                        )}
                      </View>
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
  },
  header: {
    backgroundColor: '#ffffff',
    paddingHorizontal: 20,
    paddingTop: 16,
    paddingBottom: 14,
    borderBottomWidth: 1,
    borderBottomColor: '#f1f5f9',
    shadowColor: '#0f172a',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.03,
    shadowRadius: 6,
    elevation: 2,
  },
  aiLabel: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    marginBottom: 4,
  },
  aiDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: '#22d3ee',
    marginRight: 6,
  },
  aiLabelText: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 9,
    letterSpacing: 1.1,
    color: '#0891b2',
  },
  headerTitle: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 20,
    color: '#0f172a',
  },
  headerSubtitle: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 12,
    color: '#64748b',
    marginTop: 2,
    marginBottom: 12,
  },
  filterRow: {
    flexDirection: 'row',
    gap: 8,
  },
  filterChip: {
    paddingVertical: 5,
    paddingHorizontal: 12,
    borderRadius: 20,
    backgroundColor: '#f1f5f9',
    borderWidth: 1,
    borderColor: '#e2e8f0',
  },
  filterChipActive: {
    backgroundColor: '#0056b3',
    borderColor: '#0056b3',
  },
  filterChipActiveWarning: {
    backgroundColor: '#d97706',
    borderColor: '#d97706',
  },
  filterChipActiveDanger: {
    backgroundColor: '#dc2626',
    borderColor: '#dc2626',
  },
  filterText: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 12,
    color: '#64748b',
  },
  filterTextActive: {
    fontFamily: Typography.fontFamily.bold,
    color: '#ffffff',
  },
  body: {
    flex: 1,
  },
  listContent: {
    padding: Spacing.lg,
    paddingBottom: 92,
  },
  dateHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 8,
    marginBottom: 10,
    paddingHorizontal: 4,
  },
  dateHeaderText: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 13,
    color: '#0056b3',
    letterSpacing: 0.2,
  },
  historyCard: {
    backgroundColor: '#ffffff',
    borderRadius: Radius.card,
    borderWidth: 1,
    borderColor: '#f1f5f9',
    marginBottom: 10,
    shadowColor: '#0f172a',
    shadowOffset: { width: 0, height: 1.5 },
    shadowOpacity: 0.03,
    shadowRadius: 6,
    elevation: 1.5,
    overflow: 'hidden',
    position: 'relative',
  },
  cardInner: {
    padding: 14,
  },
  cardHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  timeTag: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#eff6ff',
    paddingVertical: 3,
    paddingHorizontal: 7,
    borderRadius: 6,
  },
  timeTagText: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 11,
    color: '#0056b3',
  },
  ratingTag: {
    backgroundColor: '#ede9fe',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#ddd6fe',
    paddingHorizontal: 8,
    paddingVertical: 4,
  },
  ratingTagText: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 11,
    color: '#6d28d9',
  },
  subjectText: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 15,
    color: '#0f172a',
    marginBottom: 4,
  },
  teacherRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 6,
  },
  teacherText: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 13,
    color: '#475569',
  },
  noteBox: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    backgroundColor: '#f8fafc',
    padding: 8,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#e2e8f0',
    marginBottom: 8,
  },
  noteText: {
    fontFamily: Typography.fontFamily.regular,
    fontSize: 11,
    color: '#64748b',
    fontStyle: 'italic',
    flex: 1,
  },
  cardFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 6,
    paddingTop: 8,
    borderTopWidth: 1,
    borderTopColor: '#f8fafc',
  },
  footerRecorded: {
    fontFamily: Typography.fontFamily.regular,
    fontSize: 11,
    color: '#94a3b8',
  },
  footerArrived: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 11,
    color: '#059669',
  },
});

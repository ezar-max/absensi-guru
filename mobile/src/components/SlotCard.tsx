import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet, ActivityIndicator } from 'react-native';
import { Lock, Clock, User as UserIcon, Check, MoreHorizontal, Edit3, AlertCircle } from 'lucide-react-native';
import { StatusChip, StatusType } from './StatusChip';
import { useTheme } from '../context/ThemeContext';
import { Radius, Spacing, Typography } from '../constants/theme';

export interface SlotData {
  schedule_id: number;
  class: string;
  period: string;
  start: string;
  end: string;
  subject: string;
  teacher: string;
  attendance: {
    id: number;
    status: 'hadir' | 'terlambat' | 'kosong' | 'izin' | 'sakit' | 'dinas';
    arrived_at: string | null;
    late_minutes: number;
    note: string | null;
    created_at: string;
  } | null;
}

interface SlotCardProps {
  slot: SlotData;
  serverTime: string;
  onPressDatang: (slot: SlotData) => void;
  onPressLainnya: (slot: SlotData) => void;
  onPressEdit: (slot: SlotData) => void;
  isActionLoading?: boolean;
}

export const SlotCard: React.FC<SlotCardProps> = ({
  slot,
  serverTime,
  onPressDatang,
  onPressLainnya,
  onPressEdit,
  isActionLoading = false,
}) => {
  const { colors } = useTheme();

  // Parsing waktu server vs waktu slot
  const serverDate = new Date(serverTime);
  const currentMinutes = serverDate.getHours() * 60 + serverDate.getMinutes();

  const [startH, startM] = slot.start.split(':').map(Number);
  const [endH, endM] = slot.end.split(':').map(Number);

  const startMinutes = startH * 60 + startM;
  const endMinutes = endH * 60 + endM;

  const isBeforeStart = currentMinutes < startMinutes;
  const isOngoing = currentMinutes >= startMinutes && currentMinutes <= endMinutes;
  const isFinished = currentMinutes > endMinutes;

  const hasAttendance = slot.attendance !== null;

  // Cek jendela koreksi (15 menit default sejak attendance.created_at)
  let canEdit = false;
  let remainingEditMinutes = 0;
  if (hasAttendance && slot.attendance?.created_at) {
    const createdAt = new Date(slot.attendance.created_at);
    const elapsedMinutes = Math.floor((serverDate.getTime() - createdAt.getTime()) / (60 * 1000));
    if (elapsedMinutes <= 15) {
      canEdit = true;
      remainingEditMinutes = Math.max(1, 15 - elapsedMinutes);
    }
  }

  // Tentukan state
  let statusType: StatusType = 'belum_mulai';
  if (hasAttendance) {
    statusType = slot.attendance!.status;
  } else if (isOngoing) {
    statusType = 'belum_diabsen';
  } else if (isFinished) {
    statusType = 'belum_diabsen';
  } else {
    statusType = 'belum_mulai';
  }

  const isHighlighted = isOngoing && !hasAttendance;

  // Warna aksen bar di sebelah kiri kartu
  let accentColor = '#cbd5e1';
  if (isHighlighted) accentColor = '#0284c7';
  else if (hasAttendance) {
    switch (slot.attendance?.status) {
      case 'hadir':
        accentColor = '#10b981';
        break;
      case 'terlambat':
        accentColor = '#f59e0b';
        break;
      case 'kosong':
        accentColor = '#ef4444';
        break;
      default:
        accentColor = '#6366f1';
        break;
    }
  }

  return (
    <View
      style={[
        styles.card,
        {
          backgroundColor: colors.surface,
          borderColor: isHighlighted ? '#38bdf8' : colors.border,
          borderWidth: isHighlighted ? 1.5 : 1,
        },
      ]}
    >
      {/* Aksen strip di sebelah kiri */}
      <View style={[styles.accentStrip, { backgroundColor: accentColor }]} />

      <View style={styles.cardInner}>
        {/* Header Kartu: Jam, Periode & Status */}
        <View style={styles.headerRow}>
          <View style={styles.timeTag}>
            <Clock size={12} color="#0056b3" style={{ marginRight: 4 }} />
            <Text style={styles.timeText}>{slot.start} - {slot.end}</Text>
            <View style={styles.timeDivider} />
            <Text style={styles.periodText}>{slot.period}</Text>
          </View>

          <StatusChip
            status={statusType}
            lateMinutes={slot.attendance?.late_minutes || 0}
          />
        </View>

        {/* Sedang Berlangsung Pulse Badge */}
        {isHighlighted && (
          <View style={styles.ongoingBadge}>
            <View style={styles.pulseDot} />
            <Text style={styles.ongoingText}>SEDANG BERLANGSUNG SEKARANG</Text>
          </View>
        )}

        {/* Informasi Utama: Mata Pelajaran & Guru */}
        <View style={styles.bodySection}>
          <Text style={[styles.subjectTitle, { color: colors.text }]}>
            {slot.subject}
          </Text>

          <View style={styles.teacherContainer}>
            <View style={styles.teacherAvatar}>
              <UserIcon size={12} color="#0056b3" />
            </View>
            <Text style={[styles.teacherName, { color: colors.textSecondary }]}>
              {slot.teacher}
            </Text>
            {slot.class ? (
              <View style={styles.classBadge}>
                <Text style={styles.classBadgeText}>{slot.class}</Text>
              </View>
            ) : null}
          </View>
        </View>

        {/* Catatan / Keterangan tambahan jika ada */}
        {hasAttendance && slot.attendance?.note ? (
          <View style={styles.noteBox}>
            <Text style={styles.noteText}>Ket: {slot.attendance.note}</Text>
          </View>
        ) : null}

        {/* Area Aksi Tombol (Saat Berlangsung & Belum Diabsen) */}
        {isHighlighted && (
          <View style={styles.actionRow}>
            <TouchableOpacity
              activeOpacity={0.88}
              onPress={() => onPressDatang(slot)}
              disabled={isActionLoading}
              style={styles.btnPrimary}
            >
              {isActionLoading ? (
                <ActivityIndicator size="small" color="#ffffff" />
              ) : (
                <>
                  <Check size={16} color="#ffffff" strokeWidth={2.5} style={{ marginRight: 6 }} />
                  <Text style={styles.btnPrimaryText}>Guru Datang</Text>
                </>
              )}
            </TouchableOpacity>

            <TouchableOpacity
              activeOpacity={0.8}
              onPress={() => onPressLainnya(slot)}
              disabled={isActionLoading}
              style={styles.btnSecondary}
            >
              <MoreHorizontal size={16} color="#0056b3" style={{ marginRight: 4 }} />
              <Text style={styles.btnSecondaryText}>Opsi Lain</Text>
            </TouchableOpacity>
          </View>
        )}

        {/* Area Footer Informasi Saat Sudah Diabsen */}
        {hasAttendance && (
          <View style={styles.footerRow}>
            <View style={styles.arrivalInfo}>
              {slot.attendance?.arrived_at && (
                <Text style={styles.arrivalText}>
                  Tercatat pukul {slot.attendance.arrived_at.substring(0, 5)} WIB
                </Text>
              )}
            </View>

            {canEdit ? (
              <TouchableOpacity
                onPress={() => onPressEdit(slot)}
                style={styles.editBtn}
                hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
              >
                <Edit3 size={12} color="#0056b3" style={{ marginRight: 4 }} />
                <Text style={styles.editText}>Ubah ({remainingEditMinutes}m)</Text>
              </TouchableOpacity>
            ) : (
              <View style={styles.lockedBadge}>
                <Lock size={10} color="#94a3b8" style={{ marginRight: 3 }} />
                <Text style={styles.lockedText}>Terkunci</Text>
              </View>
            )}
          </View>
        )}

        {/* Jika Terlewat Tanpa Absensi */}
        {!hasAttendance && isFinished && (
          <View style={styles.missedNotice}>
            <AlertCircle size={13} color="#ef4444" style={{ marginRight: 5 }} />
            <Text style={styles.missedText}>
              Jam pelajaran telah selesai dan belum tercatat. Hubungi admin TU.
            </Text>
          </View>
        )}
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  card: {
    borderRadius: Radius.card,
    marginBottom: Spacing.md,
    shadowColor: '#0f172a',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 2,
    position: 'relative',
    overflow: 'hidden',
  },
  accentStrip: {
    position: 'absolute',
    left: 0,
    top: 0,
    bottom: 0,
    width: 4.5,
  },
  cardInner: {
    padding: Spacing.lg,
    paddingLeft: Spacing.lg + 4,
  },
  headerRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
  },
  timeTag: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#eff6ff',
    paddingVertical: 3.5,
    paddingHorizontal: 8,
    borderRadius: 6,
    borderWidth: 1,
    borderColor: '#dbeafe',
  },
  timeText: {
    fontFamily: Typography.fontFamily.semiBold,
    fontSize: 11,
    color: '#0056b3',
  },
  timeDivider: {
    width: 1,
    height: 10,
    backgroundColor: '#bfdbfe',
    marginHorizontal: 6,
  },
  periodText: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 11,
    color: '#0284c7',
  },
  ongoingBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#e0f2fe',
    borderWidth: 1,
    borderColor: '#7dd3fc',
    paddingVertical: 3,
    paddingHorizontal: 8,
    borderRadius: 6,
    marginBottom: 8,
    alignSelf: 'flex-start',
  },
  pulseDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: '#0284c7',
    marginRight: 6,
  },
  ongoingText: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 10,
    color: '#0369a1',
    letterSpacing: 0.5,
  },
  bodySection: {
    marginBottom: 6,
  },
  subjectTitle: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 15,
    lineHeight: 21,
    marginBottom: 6,
  },
  teacherContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    flexWrap: 'wrap',
    gap: 6,
  },
  teacherAvatar: {
    width: 20,
    height: 20,
    borderRadius: 10,
    backgroundColor: '#eff6ff',
    alignItems: 'center',
    justifyContent: 'center',
  },
  teacherName: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 13,
  },
  classBadge: {
    backgroundColor: '#f1f5f9',
    paddingVertical: 1,
    paddingHorizontal: 6,
    borderRadius: 4,
    borderWidth: 1,
    borderColor: '#e2e8f0',
  },
  classBadgeText: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 10,
    color: '#64748b',
  },
  noteBox: {
    backgroundColor: '#f8fafc',
    padding: 8,
    borderRadius: 8,
    marginTop: 6,
    borderWidth: 1,
    borderColor: '#f1f5f9',
  },
  noteText: {
    fontFamily: Typography.fontFamily.regular,
    fontSize: 11,
    color: '#64748b',
    fontStyle: 'italic',
  },
  actionRow: {
    flexDirection: 'row',
    gap: 8,
    marginTop: 12,
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: '#f1f5f9',
  },
  btnPrimary: {
    flex: 2,
    height: 42,
    backgroundColor: '#0056b3',
    borderRadius: Radius.button,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#0056b3',
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.22,
    shadowRadius: 6,
    elevation: 3,
  },
  btnPrimaryText: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 13,
    color: '#ffffff',
  },
  btnSecondary: {
    flex: 1.2,
    height: 42,
    backgroundColor: '#ffffff',
    borderRadius: Radius.button,
    borderWidth: 1.2,
    borderColor: '#0056b3',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
  },
  btnSecondaryText: {
    fontFamily: Typography.fontFamily.semiBold,
    fontSize: 12,
    color: '#0056b3',
  },
  footerRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 8,
    paddingTop: 8,
    borderTopWidth: 1,
    borderTopColor: '#f1f5f9',
  },
  arrivalInfo: {
    flex: 1,
  },
  arrivalText: {
    fontFamily: Typography.fontFamily.regular,
    fontSize: 11,
    color: '#64748b',
  },
  editBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#eff6ff',
    paddingVertical: 3.5,
    paddingHorizontal: 8,
    borderRadius: 6,
    borderWidth: 1,
    borderColor: '#bfdbfe',
  },
  editText: {
    fontFamily: Typography.fontFamily.semiBold,
    fontSize: 11,
    color: '#0056b3',
  },
  lockedBadge: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  lockedText: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 11,
    color: '#94a3b8',
  },
  missedNotice: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fef2f2',
    borderWidth: 1,
    borderColor: '#fecaca',
    padding: 8,
    borderRadius: 8,
    marginTop: 8,
  },
  missedText: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 11,
    color: '#b91c1c',
    flex: 1,
  },
});

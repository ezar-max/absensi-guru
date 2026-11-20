import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { Lock, Clock, User as UserIcon } from 'lucide-react-native';
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

  return (
    <View
      style={[
        styles.card,
        {
          backgroundColor: isHighlighted ? colors.primarySoft : colors.surface,
          borderColor: isHighlighted ? colors.primary : colors.border,
          borderWidth: isHighlighted ? 2 : 1,
        },
      ]}
    >
      {/* Label Sedang Berlangsung */}
      {isHighlighted && (
        <View style={[styles.ongoingBadge, { backgroundColor: colors.primary }]}>
          <Clock size={11} color="#FFFFFF" strokeWidth={2.5} style={{ marginRight: 4 }} />
          <Text style={styles.ongoingText}>SEDANG BERLANGSUNG</Text>
        </View>
      )}

      <View style={styles.contentRow}>
        {/* Kolom Kiri: Jam Mulai & Selesai */}
        <View style={styles.timeBlock}>
          <Text style={[styles.startTime, { color: colors.text }]}>
            {slot.start}
          </Text>
          <Text style={[styles.endTime, { color: colors.textMuted }]}>
            s.d. {slot.end}
          </Text>
          <View style={[styles.periodBadge, { backgroundColor: colors.neutralSoft }]}>
            <Text style={[styles.periodText, { color: colors.textMuted }]}>
              {slot.period}
            </Text>
          </View>
        </View>

        {/* Kolom Tengah: Info Mapel & Guru */}
        <View style={styles.infoBlock}>
          <Text style={[styles.subject, { color: colors.text }]} numberOfLines={2}>
            {slot.subject}
          </Text>

          <View style={styles.teacherRow}>
            <UserIcon size={13} color={colors.textMuted} style={{ marginRight: 4 }} />
            <Text style={[styles.teacher, { color: colors.textMuted }]} numberOfLines={1}>
              {slot.teacher}
            </Text>
          </View>

          {slot.class ? (
            <View style={[styles.classPill, { backgroundColor: colors.neutralSoft }]}>
              <Text style={[styles.classText, { color: colors.textMuted }]}>
                {slot.class}
              </Text>
            </View>
          ) : null}
        </View>

        {/* Kolom Kanan: Status */}
        <View style={styles.statusBlock}>
          <StatusChip
            status={statusType}
            lateMinutes={slot.attendance?.late_minutes || 0}
          />

          {hasAttendance && slot.attendance?.arrived_at && (
            <Text style={[styles.arrivedTime, { color: colors.textMuted }]}>
              Jam {slot.attendance.arrived_at.substring(0, 5)}
            </Text>
          )}

          {hasAttendance && canEdit && (
            <TouchableOpacity
              onPress={() => onPressEdit(slot)}
              style={styles.editLink}
              hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
            >
              <Text style={[styles.editText, { color: colors.primary }]}>
                Ubah (sisa {remainingEditMinutes}m)
              </Text>
            </TouchableOpacity>
          )}

          {hasAttendance && !canEdit && (
            <View style={styles.lockedRow}>
              <Lock size={11} color={colors.textMuted} />
              <Text style={[styles.lockedText, { color: colors.textMuted }]}>Terkunci</Text>
            </View>
          )}

          {!hasAttendance && isFinished && (
            <Text style={[styles.missedText, { color: colors.danger }]}>
              Hubungi admin
            </Text>
          )}
        </View>
      </View>

      {/* Tombol Aksi saat Sedang Berlangsung & Belum Diabsen */}
      {isHighlighted && (
        <View style={styles.actionRow}>
          <TouchableOpacity
            activeOpacity={0.85}
            onPress={() => onPressDatang(slot)}
            disabled={isActionLoading}
            style={[styles.primaryActionBtn, { backgroundColor: colors.primary }]}
          >
            <Text style={styles.primaryActionText}>Guru Datang</Text>
          </TouchableOpacity>

          <TouchableOpacity
            activeOpacity={0.8}
            onPress={() => onPressLainnya(slot)}
            disabled={isActionLoading}
            style={[styles.secondaryActionBtn, { borderColor: colors.primary, backgroundColor: colors.surface }]}
          >
            <Text style={[styles.secondaryActionText, { color: colors.primary }]}>Lainnya…</Text>
          </TouchableOpacity>
        </View>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  card: {
    borderRadius: Radius.card,
    padding: Spacing.lg,
    marginBottom: Spacing.md,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 8,
    elevation: 2,
    position: 'relative',
    overflow: 'hidden',
  },
  ongoingBadge: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 3,
  },
  ongoingText: {
    color: '#FFFFFF',
    fontFamily: Typography.fontFamily.bold,
    fontSize: 9,
    letterSpacing: 0.5,
  },
  contentRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    marginTop: 2,
  },
  timeBlock: {
    width: 68,
    borderRightWidth: 1,
    borderRightColor: '#E2E8F0',
    paddingRight: 8,
  },
  startTime: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 18,
    lineHeight: 22,
  },
  endTime: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 11,
    marginTop: 2,
  },
  periodBadge: {
    marginTop: 6,
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: Radius.chip,
    alignSelf: 'flex-start',
  },
  periodText: {
    fontFamily: Typography.fontFamily.semiBold,
    fontSize: 9,
  },
  infoBlock: {
    flex: 1,
    paddingHorizontal: 12,
  },
  subject: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 15,
    lineHeight: 20,
  },
  teacherRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 4,
  },
  teacher: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 12,
  },
  classPill: {
    alignSelf: 'flex-start',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: Radius.chip,
    marginTop: 6,
  },
  classText: {
    fontFamily: Typography.fontFamily.semiBold,
    fontSize: 10,
  },
  statusBlock: {
    alignItems: 'flex-end',
    minWidth: 80,
  },
  arrivedTime: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 11,
    marginTop: 4,
  },
  editLink: {
    marginTop: 6,
  },
  editText: {
    fontFamily: Typography.fontFamily.semiBold,
    fontSize: 11,
    textDecorationLine: 'underline',
  },
  lockedRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 6,
    gap: 3,
  },
  lockedText: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 10,
  },
  missedText: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 10,
    marginTop: 4,
  },
  actionRow: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 14,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: 'rgba(47, 91, 234, 0.15)',
  },
  primaryActionBtn: {
    flex: 2,
    height: 48,
    borderRadius: Radius.button,
    alignItems: 'center',
    justifyContent: 'center',
  },
  primaryActionText: {
    color: '#FFFFFF',
    fontFamily: Typography.fontFamily.bold,
    fontSize: 15,
  },
  secondaryActionBtn: {
    flex: 1,
    height: 48,
    borderRadius: Radius.button,
    borderWidth: 1.5,
    alignItems: 'center',
    justifyContent: 'center',
  },
  secondaryActionText: {
    fontFamily: Typography.fontFamily.semiBold,
    fontSize: 14,
  },
});


import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { Check, Clock, X, Info, AlertTriangle, CircleDashed } from 'lucide-react-native';
import { useTheme } from '../context/ThemeContext';
import { Radius, Typography } from '../constants/theme';

export type StatusType = 'hadir' | 'terlambat' | 'kosong' | 'izin' | 'sakit' | 'dinas' | 'belum_mulai' | 'belum_diabsen';

interface StatusChipProps {
  status: StatusType;
  lateMinutes?: number;
}

export const StatusChip: React.FC<StatusChipProps> = ({ status, lateMinutes = 0 }) => {
  const { colors } = useTheme();

  let label = '';
  let Icon = Check;
  let textColor = colors.text;
  let bgColor = colors.neutralSoft;
  let borderColor = colors.border;

  switch (status) {
    case 'hadir':
      label = 'Hadir Tepat Waktu';
      Icon = Check;
      textColor = '#059669';
      bgColor = '#ecfdf5';
      borderColor = '#a7f3d0';
      break;
    case 'terlambat':
      label = lateMinutes > 0 ? `Terlambat +${lateMinutes}m` : 'Terlambat';
      Icon = Clock;
      textColor = '#d97706';
      bgColor = '#fffbeb';
      borderColor = '#fde68a';
      break;
    case 'kosong':
      label = 'Guru Kosong';
      Icon = X;
      textColor = '#dc2626';
      bgColor = '#fef2f2';
      borderColor = '#fecaca';
      break;
    case 'izin':
      label = 'Izin';
      Icon = Info;
      textColor = '#4f46e5';
      bgColor = '#eef2ff';
      borderColor = '#c7d2fe';
      break;
    case 'sakit':
      label = 'Sakit';
      Icon = Info;
      textColor = '#7c3aed';
      bgColor = '#f5f3ff';
      borderColor = '#ddd6fe';
      break;
    case 'dinas':
      label = 'Tugas Dinas';
      Icon = Info;
      textColor = '#0284c7';
      bgColor = '#f0f9ff';
      borderColor = '#bae6fd';
      break;
    case 'belum_mulai':
      label = 'Belum Mulai';
      Icon = CircleDashed;
      textColor = '#64748b';
      bgColor = '#f8fafc';
      borderColor = '#e2e8f0';
      break;
    case 'belum_diabsen':
      label = 'Belum Diabsen';
      Icon = AlertTriangle;
      textColor = '#ea580c';
      bgColor = '#fff7ed';
      borderColor = '#fed7aa';
      break;
  }

  return (
    <View style={[styles.container, { backgroundColor: bgColor, borderColor }]}>
      <Icon size={12} color={textColor} strokeWidth={2.3} style={styles.icon} />
      <Text style={[styles.text, { color: textColor }]}>
        {label}
      </Text>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 9,
    paddingVertical: 4.5,
    borderRadius: Radius.chip,
    borderWidth: 1,
    alignSelf: 'flex-start',
  },
  icon: {
    marginRight: 4,
  },
  text: {
    fontFamily: Typography.fontFamily.semiBold,
    fontSize: 11,
    lineHeight: 15,
  },
});

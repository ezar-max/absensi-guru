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

  switch (status) {
    case 'hadir':
      label = 'Hadir';
      Icon = Check;
      textColor = colors.success;
      bgColor = colors.successSoft;
      break;
    case 'terlambat':
      label = lateMinutes > 0 ? `Terlambat +${lateMinutes}m` : 'Terlambat';
      Icon = Clock;
      textColor = colors.warning;
      bgColor = colors.warningSoft;
      break;
    case 'kosong':
      label = 'Kosong';
      Icon = X;
      textColor = colors.danger;
      bgColor = colors.dangerSoft;
      break;
    case 'izin':
      label = 'Izin';
      Icon = Info;
      textColor = colors.info;
      bgColor = colors.infoSoft;
      break;
    case 'sakit':
      label = 'Sakit';
      Icon = Info;
      textColor = colors.info;
      bgColor = colors.infoSoft;
      break;
    case 'dinas':
      label = 'Dinas';
      Icon = Info;
      textColor = colors.info;
      bgColor = colors.infoSoft;
      break;
    case 'belum_mulai':
      label = 'Belum mulai';
      Icon = CircleDashed;
      textColor = colors.neutral;
      bgColor = colors.neutralSoft;
      break;
    case 'belum_diabsen':
      label = 'Belum diabsen';
      Icon = AlertTriangle;
      textColor = colors.warning;
      bgColor = colors.warningSoft;
      break;
  }

  return (
    <View style={[styles.container, { backgroundColor: bgColor }]}>
      <Icon size={13} color={textColor} strokeWidth={2.5} style={styles.icon} />
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
    paddingVertical: 5,
    borderRadius: Radius.chip,
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


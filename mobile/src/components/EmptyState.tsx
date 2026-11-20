import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { CalendarX2 } from 'lucide-react-native';
import { useTheme } from '../context/ThemeContext';
import { Spacing, Typography } from '../constants/theme';

interface EmptyStateProps {
  title: string;
  message?: string;
  icon?: React.ReactNode;
}

export const EmptyState: React.FC<EmptyStateProps> = ({ title, message, icon }) => {
  const { colors } = useTheme();

  return (
    <View style={styles.container}>
      {icon ? (
        <View style={styles.iconContainer}>{icon}</View>
      ) : (
        <CalendarX2 size={48} color={colors.neutral} style={styles.iconContainer} />
      )}
      <Text style={[styles.title, { color: colors.text }]}>{title}</Text>
      {message && <Text style={[styles.message, { color: colors.textMuted }]}>{message}</Text>}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    padding: Spacing.xxl,
    alignItems: 'center',
    justifyContent: 'center',
    marginVertical: Spacing.xl,
  },
  iconContainer: {
    marginBottom: Spacing.md,
  },
  title: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 16,
    textAlign: 'center',
    marginBottom: Spacing.xs,
  },
  message: {
    fontFamily: Typography.fontFamily.regular,
    fontSize: 13,
    textAlign: 'center',
    lineHeight: 18,
  },
});


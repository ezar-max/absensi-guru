import React, { useEffect, useRef } from 'react';
import { View, Animated, StyleSheet } from 'react-native';
import { useTheme } from '../context/ThemeContext';
import { Radius, Spacing } from '../constants/theme';

export const SkeletonCard: React.FC = () => {
  const { colors } = useTheme();
  const opacity = useRef(new Animated.Value(0.3)).current;

  useEffect(() => {
    const loop = Animated.loop(
      Animated.sequence([
        Animated.timing(opacity, {
          toValue: 0.8,
          duration: 800,
          useNativeDriver: true,
        }),
        Animated.timing(opacity, {
          toValue: 0.3,
          duration: 800,
          useNativeDriver: true,
        }),
      ])
    );
    loop.start();
    return () => loop.stop();
  }, [opacity]);

  return (
    <View style={[styles.card, { backgroundColor: colors.surface, borderColor: colors.border }]}>
      <View style={styles.row}>
        {/* Time block */}
        <Animated.View
          style={[
            styles.timePlaceholder,
            { backgroundColor: colors.neutralSoft, opacity },
          ]}
        />
        {/* Info block */}
        <View style={styles.info}>
          <Animated.View
            style={[
              styles.lineLong,
              { backgroundColor: colors.neutralSoft, opacity },
            ]}
          />
          <Animated.View
            style={[
              styles.lineShort,
              { backgroundColor: colors.neutralSoft, opacity },
            ]}
          />
        </View>
        {/* Chip placeholder */}
        <Animated.View
          style={[
            styles.chipPlaceholder,
            { backgroundColor: colors.neutralSoft, opacity },
          ]}
        />
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  card: {
    borderRadius: Radius.card,
    padding: Spacing.lg,
    borderWidth: 1,
    marginBottom: Spacing.md,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  timePlaceholder: {
    width: 60,
    height: 48,
    borderRadius: 8,
  },
  info: {
    flex: 1,
    paddingHorizontal: 16,
    gap: 8,
  },
  lineLong: {
    height: 16,
    width: '85%',
    borderRadius: 4,
  },
  lineShort: {
    height: 12,
    width: '55%',
    borderRadius: 4,
  },
  chipPlaceholder: {
    width: 70,
    height: 26,
    borderRadius: Radius.chip,
  },
});


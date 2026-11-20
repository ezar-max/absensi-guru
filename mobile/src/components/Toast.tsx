import React, { useEffect, useRef } from 'react';
import { Text, Animated, StyleSheet, Platform } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { CheckCircle2, AlertCircle } from 'lucide-react-native';
import { useTheme } from '../context/ThemeContext';
import { Radius, Spacing, Typography } from '../constants/theme';

interface ToastProps {
  visible: boolean;
  message: string;
  type?: 'success' | 'error';
  onDismiss: () => void;
}

export const Toast: React.FC<ToastProps> = ({
  visible,
  message,
  type = 'success',
  onDismiss,
}) => {
  const { colors } = useTheme();
  const translateY = useRef(new Animated.Value(-100)).current;

  useEffect(() => {
    if (visible) {
      Animated.spring(translateY, {
        toValue: 0,
        useNativeDriver: true,
      }).start();

      const timer = setTimeout(() => {
        Animated.timing(translateY, {
          toValue: -100,
          duration: 300,
          useNativeDriver: true,
        }).start(() => onDismiss());
      }, 3000);

      return () => clearTimeout(timer);
    }
  }, [visible]);

  if (!visible) return null;

  const isSuccess = type === 'success';
  const bgColor = isSuccess ? colors.success : colors.danger;

  return (
    <SafeAreaView style={styles.safeArea} pointerEvents="none">
      <Animated.View
        style={[
          styles.container,
          { backgroundColor: bgColor, transform: [{ translateY }] },
        ]}
      >
        {isSuccess ? (
          <CheckCircle2 size={18} color="#FFFFFF" style={{ marginRight: 8 }} />
        ) : (
          <AlertCircle size={18} color="#FFFFFF" style={{ marginRight: 8 }} />
        )}
        <Text style={styles.text}>{message}</Text>
      </Animated.View>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  safeArea: {
    position: 'absolute',
    top: Platform.OS === 'android' ? 20 : 0,
    left: 0,
    right: 0,
    zIndex: 9999,
    alignItems: 'center',
  },
  container: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderRadius: Radius.button,
    marginHorizontal: Spacing.lg,
    maxWidth: '90%',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.15,
    shadowRadius: 10,
    elevation: 6,
  },
  text: {
    color: '#FFFFFF',
    fontFamily: Typography.fontFamily.semiBold,
    fontSize: 13,
    flexShrink: 1,
  },
});


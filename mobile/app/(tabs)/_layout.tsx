import React from 'react';
import { Platform } from 'react-native';
import { Tabs } from 'expo-router';
import { CalendarDays, ClipboardCheck, History, User } from 'lucide-react-native';
import { useTheme } from '../../src/context/ThemeContext';
import { Typography } from '../../src/constants/theme';

export default function TabsLayout() {
  const { colors } = useTheme();

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.textMuted,
        tabBarStyle: {
          backgroundColor: colors.surface,
          borderTopWidth: 0,
          borderWidth: 1,
          borderColor: colors.border,
          borderRadius: 26,
          height: Platform.OS === 'ios' ? 82 : 76,
          marginHorizontal: 12,
          marginBottom: Platform.OS === 'ios' ? 8 : 6,
          paddingBottom: Platform.OS === 'ios' ? 14 : 10,
          paddingTop: 9,
          paddingHorizontal: 4,
          position: 'absolute',
          elevation: 12,
          shadowColor: '#0f172a',
          shadowOffset: { width: 0, height: 6 },
          shadowOpacity: 0.12,
          shadowRadius: 14,
        },
        tabBarLabelStyle: {
          fontFamily: Typography.fontFamily.semiBold,
          fontSize: 12,
          marginTop: 2,
        },
      }}
    >
      <Tabs.Screen
        name="index"
        options={{
          title: 'Hari Ini',
          tabBarIcon: ({ color, focused }) => (
            <CalendarDays size={focused ? 22 : 20} color={color} strokeWidth={focused ? 2.3 : 1.8} />
          ),
        }}
      />
      <Tabs.Screen
        name="history"
        options={{
          title: 'Riwayat',
          tabBarIcon: ({ color, size, focused }) => (
            <History size={focused ? 22 : 20} color={color} strokeWidth={focused ? 2.3 : 1.8} />
          ),
        }}
      />
      <Tabs.Screen
        name="rating"
        options={{
          title: 'Nilai Guru',
          tabBarIcon: ({ color, focused }) => (
            <ClipboardCheck size={focused ? 22 : 20} color={color} strokeWidth={focused ? 2.3 : 1.8} />
          ),
        }}
      />
      <Tabs.Screen
        name="profile"
        options={{
          title: 'Profil',
          tabBarIcon: ({ color, size, focused }) => (
            <User size={focused ? 22 : 20} color={color} strokeWidth={focused ? 2.3 : 1.8} />
          ),
        }}
      />
    </Tabs>
  );
}

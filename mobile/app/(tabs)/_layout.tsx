import React from 'react';
import { Platform } from 'react-native';
import { Tabs } from 'expo-router';
import { CalendarDays, History, User } from 'lucide-react-native';
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
          borderTopColor: colors.border,
          borderTopWidth: 1,
          height: Platform.OS === 'ios' ? 84 : 64,
          paddingBottom: Platform.OS === 'ios' ? 24 : 10,
          paddingTop: 10,
          elevation: 8,
          shadowColor: '#0f172a',
          shadowOffset: { width: 0, height: -3 },
          shadowOpacity: 0.04,
          shadowRadius: 8,
        },
        tabBarLabelStyle: {
          fontFamily: Typography.fontFamily.semiBold,
          fontSize: 11,
          marginTop: 2,
        },
      }}
    >
      <Tabs.Screen
        name="index"
        options={{
          title: 'Hari Ini',
          tabBarIcon: ({ color, size, focused }) => (
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

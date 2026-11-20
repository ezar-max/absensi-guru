import React, { useState } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  Alert,
  ScrollView,
  Platform,
  StatusBar,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { User, LogOut, Sun, Moon, Smartphone, Shield, BookOpen } from 'lucide-react-native';
import { useAuth } from '../../src/context/AuthContext';
import { useTheme } from '../../src/context/ThemeContext';
import { Radius, Spacing, Typography } from '../../src/constants/theme';
import { PrimaryButton } from '../../src/components/PrimaryButton';

export default function ProfileScreen() {
  const { user, classes, logout } = useAuth();
  const { mode, setMode, colors } = useTheme();

  const [loggingOut, setLoggingOut] = useState(false);

  const handleLogout = () => {
    Alert.alert(
      'Konfirmasi Keluar',
      'Apakah Anda yakin ingin keluar dari aplikasi Absensi Guru?',
      [
        { text: 'Batal', style: 'cancel' },
        {
          text: 'Keluar',
          style: 'destructive',
          onPress: async () => {
            setLoggingOut(true);
            await logout();
            setLoggingOut(false);
          },
        },
      ]
    );
  };

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: colors.bg }]}>
      <ScrollView contentContainerStyle={styles.scrollContent}>
        {/* Header Title */}
        <View style={styles.header}>
          <Text style={[styles.headerTitle, { color: colors.text }]}>Profil Saya</Text>
        </View>

        {/* User Card */}
        <View style={[styles.card, { backgroundColor: colors.surface, borderColor: colors.border }]}>
          <View style={[styles.avatarBadge, { backgroundColor: colors.primarySoft }]}>
            <User size={36} color={colors.primary} />
          </View>
          <Text style={[styles.userName, { color: colors.text }]}>{user?.name}</Text>
          <Text style={[styles.userEmail, { color: colors.textMuted }]}>
            {user?.nis ? `NIS: ${user.nis}` : user?.email}
          </Text>

          <View style={[styles.roleBadge, { backgroundColor: colors.primarySoft }]}>
            <Shield size={12} color={colors.primary} style={{ marginRight: 4 }} />
            <Text style={[styles.roleText, { color: colors.primary }]}>Sekretaris Kelas</Text>
          </View>
        </View>

        {/* Classes Card */}
        <View style={[styles.sectionCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
          <Text style={[styles.sectionTitle, { color: colors.text }]}>
            Kelas yang Dipegang
          </Text>
          <View style={styles.classesList}>
            {classes.length > 0 ? (
              classes.map((c) => (
                <View
                  key={c.id}
                  style={[styles.classItem, { backgroundColor: colors.bg, borderColor: colors.border }]}
                >
                  <BookOpen size={16} color={colors.primary} style={{ marginRight: 8 }} />
                  <Text style={[styles.className, { color: colors.text }]}>{c.name}</Text>
                </View>
              ))
            ) : (
              <Text style={[styles.emptyText, { color: colors.textMuted }]}>
                Tidak ada kelas yang terhubung
              </Text>
            )}
          </View>
        </View>

        {/* Theme Picker Card */}
        <View style={[styles.sectionCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
          <Text style={[styles.sectionTitle, { color: colors.text }]}>
            Pilihan Tema Tampilan
          </Text>
          <View style={styles.themeOptionsRow}>
            {/* Sistem */}
            <TouchableOpacity
              onPress={() => setMode('system')}
              style={[
                styles.themeBtn,
                {
                  borderColor: mode === 'system' ? colors.primary : colors.border,
                  backgroundColor: mode === 'system' ? colors.primarySoft : colors.bg,
                },
              ]}
            >
              <Smartphone size={18} color={mode === 'system' ? colors.primary : colors.textMuted} />
              <Text
                style={[
                  styles.themeBtnText,
                  { color: mode === 'system' ? colors.primary : colors.text },
                ]}
              >
                Sistem
              </Text>
            </TouchableOpacity>

            {/* Terang */}
            <TouchableOpacity
              onPress={() => setMode('light')}
              style={[
                styles.themeBtn,
                {
                  borderColor: mode === 'light' ? colors.primary : colors.border,
                  backgroundColor: mode === 'light' ? colors.primarySoft : colors.bg,
                },
              ]}
            >
              <Sun size={18} color={mode === 'light' ? colors.primary : colors.textMuted} />
              <Text
                style={[
                  styles.themeBtnText,
                  { color: mode === 'light' ? colors.primary : colors.text },
                ]}
              >
                Terang
              </Text>
            </TouchableOpacity>

            {/* Gelap */}
            <TouchableOpacity
              onPress={() => setMode('dark')}
              style={[
                styles.themeBtn,
                {
                  borderColor: mode === 'dark' ? colors.primary : colors.border,
                  backgroundColor: mode === 'dark' ? colors.primarySoft : colors.bg,
                },
              ]}
            >
              <Moon size={18} color={mode === 'dark' ? colors.primary : colors.textMuted} />
              <Text
                style={[
                  styles.themeBtnText,
                  { color: mode === 'dark' ? colors.primary : colors.text },
                ]}
              >
                Gelap
              </Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* App Info */}
        <View style={styles.appInfoRow}>
          <Text style={[styles.appVersion, { color: colors.textMuted }]}>
            Aplikasi Absensi Guru Sekolah • Versi 1.0.0
          </Text>
        </View>

        {/* Logout Button */}
        <PrimaryButton
          title="Keluar dari Aplikasi"
          onPress={handleLogout}
          variant="danger"
          loading={loggingOut}
          icon={<LogOut size={18} color="#FFFFFF" />}
        />
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight : 0,
  },
  scrollContent: {
    padding: Spacing.lg,
    paddingBottom: 40,
  },
  header: {
    marginBottom: Spacing.lg,
    paddingTop: Spacing.sm,
  },
  headerTitle: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 22,
  },
  card: {
    borderRadius: Radius.card,
    padding: Spacing.xl,
    alignItems: 'center',
    borderWidth: 1,
    marginBottom: Spacing.md,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 8,
    elevation: 2,
  },
  avatarBadge: {
    width: 72,
    height: 72,
    borderRadius: 36,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: Spacing.sm,
  },
  userName: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 18,
    textAlign: 'center',
  },
  userEmail: {
    fontFamily: Typography.fontFamily.regular,
    fontSize: 13,
    marginTop: 2,
  },
  roleBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 5,
    borderRadius: Radius.chip,
    marginTop: 10,
  },
  roleText: {
    fontFamily: Typography.fontFamily.semiBold,
    fontSize: 12,
  },
  sectionCard: {
    borderRadius: Radius.card,
    padding: Spacing.lg,
    borderWidth: 1,
    marginBottom: Spacing.md,
  },
  sectionTitle: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 15,
    marginBottom: Spacing.md,
  },
  classesList: {
    gap: 8,
  },
  classItem: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
    borderRadius: Radius.input,
    borderWidth: 1,
  },
  className: {
    fontFamily: Typography.fontFamily.semiBold,
    fontSize: 14,
  },
  emptyText: {
    fontFamily: Typography.fontFamily.regular,
    fontSize: 13,
    fontStyle: 'italic',
  },
  themeOptionsRow: {
    flexDirection: 'row',
    gap: 10,
  },
  themeBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    paddingVertical: 12,
    borderRadius: Radius.button,
    borderWidth: 1.5,
  },
  themeBtnText: {
    fontFamily: Typography.fontFamily.semiBold,
    fontSize: 13,
  },
  appInfoRow: {
    alignItems: 'center',
    marginVertical: Spacing.lg,
  },
  appVersion: {
    fontFamily: Typography.fontFamily.regular,
    fontSize: 12,
  },
});


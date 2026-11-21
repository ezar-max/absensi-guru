import React, { useState } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  Alert,
  ScrollView,
  ActivityIndicator,
  Image,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import {
  User,
  LogOut,
  Shield,
  BookOpen,
} from 'lucide-react-native';
import { useAuth } from '../../src/context/AuthContext';
import { useTheme } from '../../src/context/ThemeContext';
import { Radius, Spacing, Typography } from '../../src/constants/theme';

export default function ProfileScreen() {
  const { user, classes, logout } = useAuth();
  const { colors } = useTheme();

  const [loggingOut, setLoggingOut] = useState(false);


  const handleLogout = () => {
    Alert.alert(
      'Konfirmasi Keluar',
      'Apakah Anda yakin ingin keluar dari akun sekretaris?',
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

  // Inisial avatar
  const initials = (user?.name || 'Sekretaris')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((w) => w[0].toUpperCase())
    .join('');

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: colors.bg }]}>
      <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
        {/* Header Title */}
        <View style={styles.header}>
          <View style={styles.aiLabel}>
            <View style={styles.aiDot} />
            <Text style={styles.aiLabelText}>PERSONAL CONTROL</Text>
          </View>
          <Text style={[styles.headerTitle, { color: colors.text }]}>Profil & Pengaturan</Text>
          <Text style={[styles.headerSubtitle, { color: colors.textMuted }]}>Kelola akun sekretaris dan preferensi aplikasi</Text>
        </View>

        {/* Profil Sekretaris Card */}
        <View style={styles.profileCard}>
          <View style={styles.profileAccent} />
          <View style={styles.avatarCircle}>
            <Text style={styles.avatarInitials}>{initials}</Text>
          </View>

          <Text style={styles.userName}>{user?.name}</Text>
          {user?.nis ? (
            <View style={styles.nisPill}>
              <Text style={styles.nisText}>NIS: {user.nis}</Text>
            </View>
          ) : (
            <Text style={styles.userEmail}>{user?.email}</Text>
          )}

          <View style={styles.roleBadge}>
            <Shield size={12} color="#0056b3" style={{ marginRight: 4 }} />
            <Text style={styles.roleText}>Sekretaris Kelas</Text>
          </View>
        </View>

        {/* Kelas yang Ditugaskan */}
        <View style={styles.sectionCard}>
          <View style={styles.sectionHeaderRow}>
            <BookOpen size={16} color="#0056b3" style={{ marginRight: 6 }} />
            <Text style={styles.sectionTitle}>Kelas yang Ditugaskan</Text>
          </View>

          <View style={styles.classesList}>
            {classes.length > 0 ? (
              classes.map((c) => (
                <View key={c.id} style={styles.classItem}>
                  <View style={styles.classDot} />
                  <Text style={styles.className}>{c.name}</Text>
                </View>
              ))
            ) : (
              <Text style={styles.emptyText}>Tidak ada kelas yang terhubung ke akun Anda</Text>
            )}
          </View>
        </View>

        {/* Tentang Aplikasi & Sekolah */}
        <View style={styles.aboutCard}>
          <Image
            source={require('../../assets/logo-tutwuri.png')}
            style={styles.aboutLogo}
            resizeMode="contain"
          />
          <Text style={styles.aboutSchoolName}>SMKN 1 GUNUNGPUTRI</Text>
          <Text style={styles.aboutAppTitle}>Sistem Absensi Guru Digital</Text>
          <Text style={styles.aboutVersion}>Versi 1.0.0 — Build 2026</Text>
          <Text style={styles.aboutMotto}>"Disiplin Hari Ini, Prestasi Esok Nanti"</Text>
        </View>

        {/* Tombol Logout */}
        <TouchableOpacity
          onPress={handleLogout}
          disabled={loggingOut}
          style={styles.logoutBtn}
          activeOpacity={0.85}
        >
          {loggingOut ? (
            <ActivityIndicator size="small" color="#dc2626" />
          ) : (
            <>
              <LogOut size={16} color="#dc2626" style={{ marginRight: 8 }} />
              <Text style={styles.logoutText}>Keluar dari Aplikasi</Text>
            </>
          )}
        </TouchableOpacity>
      </ScrollView>

    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  scrollContent: {
    padding: 20,
    paddingBottom: 92,
  },
  header: {
    marginBottom: 16,
  },
  aiLabel: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 4,
  },
  aiDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: '#22d3ee',
    marginRight: 6,
  },
  aiLabelText: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 9,
    letterSpacing: 1.1,
    color: '#0891b2',
  },
  headerTitle: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 22,
    color: '#0f172a',
  },
  headerSubtitle: {
    fontFamily: Typography.fontFamily.regular,
    fontSize: 12,
    color: '#64748b',
    marginTop: 2,
  },
  profileCard: {
    backgroundColor: '#ffffff',
    borderRadius: Radius.card,
    padding: 20,
    alignItems: 'center',
    marginBottom: 16,
    borderWidth: 1,
    borderColor: '#e5edf8',
    overflow: 'hidden',
    shadowColor: '#0f172a',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.03,
    shadowRadius: 8,
    elevation: 2,
  },
  profileAccent: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: 58,
    backgroundColor: '#eaf2ff',
  },
  avatarCircle: {
    width: 64,
    height: 64,
    borderRadius: 32,
    backgroundColor: '#0056b3',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
    shadowColor: '#0056b3',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.25,
    shadowRadius: 8,
    elevation: 4,
  },
  avatarInitials: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 22,
    color: '#ffffff',
    letterSpacing: 1,
  },
  userName: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 18,
    color: '#0f172a',
    textAlign: 'center',
  },
  nisPill: {
    backgroundColor: '#eff6ff',
    paddingVertical: 3,
    paddingHorizontal: 10,
    borderRadius: 12,
    marginTop: 4,
    borderWidth: 1,
    borderColor: '#dbeafe',
  },
  nisText: {
    fontFamily: Typography.fontFamily.semiBold,
    fontSize: 12,
    color: '#0056b3',
  },
  userEmail: {
    fontFamily: Typography.fontFamily.regular,
    fontSize: 12,
    color: '#64748b',
    marginTop: 3,
  },
  roleBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#f1f5f9',
    paddingVertical: 4,
    paddingHorizontal: 10,
    borderRadius: 20,
    marginTop: 10,
  },
  roleText: {
    fontFamily: Typography.fontFamily.semiBold,
    fontSize: 11,
    color: '#0056b3',
  },
  sectionCard: {
    backgroundColor: '#ffffff',
    borderRadius: Radius.card,
    padding: 16,
    marginBottom: 14,
    borderWidth: 1,
    borderColor: '#f1f5f9',
    shadowColor: '#0f172a',
    shadowOffset: { width: 0, height: 1.5 },
    shadowOpacity: 0.03,
    shadowRadius: 6,
    elevation: 1.5,
  },
  sectionHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 10,
  },
  sectionTitle: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 14,
    color: '#0f172a',
  },
  classesList: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  classItem: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#eff6ff',
    paddingVertical: 6,
    paddingHorizontal: 12,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#bfdbfe',
  },
  classDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: '#0056b3',
    marginRight: 6,
  },
  className: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 13,
    color: '#0056b3',
  },
  emptyText: {
    fontFamily: Typography.fontFamily.regular,
    fontSize: 12,
    color: '#94a3b8',
    fontStyle: 'italic',
  },
  serverInfoBox: {
    backgroundColor: '#f8fafc',
    padding: 10,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#e2e8f0',
    marginBottom: 10,
  },
  serverInfoLabel: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 11,
    color: '#64748b',
    marginBottom: 2,
  },
  serverInfoUrl: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 12,
    color: '#0056b3',
  },
  serverConfigBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderRadius: 8,
    backgroundColor: '#eff6ff',
    borderWidth: 1,
    borderColor: '#bfdbfe',
  },
  serverConfigBtnText: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 12,
    color: '#0056b3',
  },
  themeOptionsRow: {
    flexDirection: 'row',
    gap: 8,
    marginTop: 10,
  },
  themeBtn: {
    flex: 1,
    height: 38,
    borderRadius: 8,
    backgroundColor: '#f8fafc',
    borderWidth: 1,
    borderColor: '#e2e8f0',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
  },
  themeBtnActive: {
    backgroundColor: '#eff6ff',
    borderColor: '#0056b3',
  },
  themeBtnText: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 12,
    color: '#64748b',
  },
  themeBtnTextActive: {
    color: '#0056b3',
    fontFamily: Typography.fontFamily.bold,
  },
  aboutCard: {
    backgroundColor: '#f8fafc',
    borderRadius: Radius.card,
    padding: 18,
    alignItems: 'center',
    marginBottom: 16,
    borderWidth: 1,
    borderColor: '#e2e8f0',
  },
  aboutLogo: {
    width: 42,
    height: 42,
    marginBottom: 6,
  },
  aboutSchoolName: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 13,
    color: '#0056b3',
    letterSpacing: 1,
  },
  aboutAppTitle: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 12,
    color: '#0f172a',
    marginTop: 1,
  },
  aboutVersion: {
    fontFamily: Typography.fontFamily.regular,
    fontSize: 10,
    color: '#94a3b8',
    marginTop: 2,
  },
  aboutMotto: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 11,
    color: '#0369a1',
    fontStyle: 'italic',
    marginTop: 8,
  },
  logoutBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#fef2f2',
    borderWidth: 1.2,
    borderColor: '#fca5a5',
    paddingVertical: 12,
    borderRadius: Radius.button,
  },
  logoutText: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 13,
    color: '#dc2626',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.65)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
  },
  modalCard: {
    width: '100%',
    backgroundColor: '#ffffff',
    borderRadius: 20,
    padding: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.15,
    shadowRadius: 20,
    elevation: 8,
  },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 8,
  },
  modalTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  modalTitle: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 16,
    color: '#0f172a',
  },
  modalSubtitle: {
    fontFamily: Typography.fontFamily.regular,
    fontSize: 12,
    color: '#64748b',
    marginBottom: 16,
    lineHeight: 18,
  },
  serverInputContainer: {
    backgroundColor: '#f8fafc',
    borderWidth: 1.2,
    borderColor: '#cbd5e1',
    borderRadius: 12,
    paddingHorizontal: 14,
    height: 46,
    justifyContent: 'center',
    marginBottom: 10,
  },
  serverTextInput: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 13,
    color: '#0f172a',
    height: '100%',
  },
  presetContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
    marginBottom: 14,
  },
  presetChip: {
    backgroundColor: '#eff6ff',
    paddingVertical: 6,
    paddingHorizontal: 12,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#bfdbfe',
  },
  presetChipText: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 11,
    color: '#0056b3',
  },
  testStatusBox: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 10,
    borderRadius: 10,
    marginBottom: 14,
  },
  testStatusSuccess: {
    backgroundColor: '#f0fdf4',
    borderWidth: 1,
    borderColor: '#bbf7d0',
  },
  testStatusFail: {
    backgroundColor: '#fef2f2',
    borderWidth: 1,
    borderColor: '#fecaca',
  },
  testStatusText: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 12,
    flex: 1,
  },
  testTextSuccess: {
    color: '#16a34a',
  },
  testTextFail: {
    color: '#dc2626',
  },
  modalActions: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 4,
  },
  testButton: {
    flex: 1,
    height: 42,
    borderRadius: 10,
    borderWidth: 1.2,
    borderColor: '#0056b3',
    backgroundColor: '#ffffff',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
  },
  testButtonText: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 13,
    color: '#0056b3',
  },
  saveButton: {
    flex: 1.2,
    height: 42,
    borderRadius: 10,
    backgroundColor: '#0056b3',
    alignItems: 'center',
    justifyContent: 'center',
  },
  saveButtonText: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 13,
    color: '#ffffff',
  },
});

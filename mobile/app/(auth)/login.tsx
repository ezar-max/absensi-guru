import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  KeyboardAvoidingView,
  Platform,
  TouchableWithoutFeedback,
  Keyboard,
  ScrollView,
  Image,
  ActivityIndicator,
  Dimensions,
  Modal,
} from 'react-native';
import { StatusBar } from 'expo-status-bar';
import Svg, { Path, Defs, LinearGradient, Stop, Rect } from 'react-native-svg';
import { User, Lock, Eye, EyeOff, ArrowRight, Server, X, CheckCircle2, AlertCircle, RefreshCw } from 'lucide-react-native';
import * as SecureStore from 'expo-secure-store';
import axios from 'axios';
import { useAuth } from '../../src/context/AuthContext';
import { useTheme } from '../../src/context/ThemeContext';
import { Typography } from '../../src/constants/theme';
import { getErrorMessage, SERVER_URL_KEY, API_BASE_URL } from '../../src/services/api';

const { width: SCREEN_WIDTH, height: SCREEN_HEIGHT } = Dimensions.get('window');

export default function LoginScreen() {
  const { login } = useAuth();
  const { colors } = useTheme();

  const [usernameOrNis, setUsernameOrNis] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const [serverModalVisible, setServerModalVisible] = useState(false);
  const [serverUrlInput, setServerUrlInput] = useState(API_BASE_URL);
  const [testStatus, setTestStatus] = useState<{ loading: boolean; success?: boolean; message?: string } | null>(null);

  useEffect(() => {
    SecureStore.getItemAsync(SERVER_URL_KEY).then((saved) => {
      if (saved) setServerUrlInput(saved);
    });
  }, []);

  const openServerSettings = () => {
    setTestStatus(null);
    setServerModalVisible(true);
  };

  const handleTestConnection = async () => {
    const target = serverUrlInput.trim().replace(/\/+$/, '');
    setTestStatus({ loading: true });
    try {
      const res = await axios.get(`${target}/ping`, { timeout: 4000 });
      setTestStatus({ loading: false, success: true, message: `Terhubung! (Status: ${res.status})` });
    } catch (e: any) {
      if (e.response) {
        setTestStatus({ loading: false, success: true, message: `Terhubung ke API! (Status: ${e.response.status})` });
      } else {
        setTestStatus({ loading: false, success: false, message: e.message || 'Koneksi gagal / timeout' });
      }
    }
  };

  const handleSaveServerUrl = async () => {
    const target = serverUrlInput.trim().replace(/\/+$/, '');
    await SecureStore.setItemAsync(SERVER_URL_KEY, target);
    setServerModalVisible(false);
    setErrorMessage(null);
  };

  const handleLogin = async () => {
    if (!usernameOrNis.trim() || !password.trim()) {
      setErrorMessage('Username/NIS dan kata sandi wajib diisi.');
      return;
    }

    setLoading(true);
    setErrorMessage(null);

    try {
      await login(usernameOrNis.trim(), password);
    } catch (error: any) {
      setErrorMessage(getErrorMessage(error));
    } finally {
      setLoading(false);
    }
  };

  return (
    <TouchableWithoutFeedback onPress={Keyboard.dismiss}>
      <View style={[styles.rootContainer, { backgroundColor: '#f8fafc' }]}>
        <StatusBar style="dark" />

        {/* Top Decorative Blue Waves (matching the reference design) */}
        <View style={styles.topWaveContainer} pointerEvents="none">
          <Svg
            height="180"
            width={SCREEN_WIDTH}
            viewBox={`0 0 ${SCREEN_WIDTH} 180`}
            style={StyleSheet.absoluteFill}
          >
            <Defs>
              <LinearGradient id="wave1" x1="0%" y1="0%" x2="100%" y2="100%">
                <Stop offset="0%" stopColor="#bae6fd" stopOpacity="0.6" />
                <Stop offset="60%" stopColor="#e0f2fe" stopOpacity="0.4" />
                <Stop offset="100%" stopColor="#f8fafc" stopOpacity="0" />
              </LinearGradient>
              <LinearGradient id="wave2" x1="100%" y1="0%" x2="0%" y2="100%">
                <Stop offset="0%" stopColor="#7dd3fc" stopOpacity="0.4" />
                <Stop offset="100%" stopColor="#f8fafc" stopOpacity="0" />
              </LinearGradient>
            </Defs>
            <Path
              d={`M0,0 L${SCREEN_WIDTH},0 L${SCREEN_WIDTH},90 C${SCREEN_WIDTH * 0.75},150 ${SCREEN_WIDTH * 0.3},40 0,110 Z`}
              fill="url(#wave1)"
            />
            <Path
              d={`M0,0 L${SCREEN_WIDTH},0 L${SCREEN_WIDTH},40 C${SCREEN_WIDTH * 0.6},100 ${SCREEN_WIDTH * 0.2},70 0,60 Z`}
              fill="url(#wave2)"
            />
          </Svg>
        </View>

        <KeyboardAvoidingView
          behavior={Platform.OS === 'ios' ? 'padding' : undefined}
          style={styles.keyboardContainer}
        >
          <ScrollView
            contentContainerStyle={styles.scrollContent}
            keyboardShouldPersistTaps="handled"
            showsVerticalScrollIndicator={false}
          >
            {/* Top Branding Section */}
            <View style={styles.headerSection}>
              {/* Tombol Pengaturan Server */}
              <TouchableOpacity
                onPress={openServerSettings}
                style={styles.serverSettingsBtn}
                hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
                activeOpacity={0.8}
              >
                <Server size={18} color="#0056b3" />
              </TouchableOpacity>

              <Image
                source={require('../../assets/logo-tutwuri.png')}
                style={styles.logoImage}
                resizeMode="contain"
              />
              <View style={styles.aiWelcomeBadge}>
                <View style={styles.aiWelcomeDot} />
                <Text style={styles.aiWelcomeText}>SMART CAMPUS ASSISTANT</Text>
              </View>
              <Text style={styles.schoolNameLine1}>SMKN 1</Text>
              <Text style={styles.schoolNameLine2}>GUNUNG PUTRI</Text>

              <Text style={styles.welcomeTitle}>Selamat Datang</Text>
              <Text style={styles.welcomeSubtitle}>Silakan login untuk melanjutkan</Text>
            </View>

            {/* Login Form Section */}
            <View style={styles.formSection}>
              {errorMessage && (
                <TouchableOpacity
                  onPress={openServerSettings}
                  activeOpacity={0.88}
                  style={styles.errorAlert}
                >
                  <Text style={styles.errorText}>{errorMessage}</Text>
                  <Text style={styles.errorHint}>Ketuk untuk periksa / ubah alamat IP Server</Text>
                </TouchableOpacity>
              )}

              {/* Input Username atau NIS */}
              <View style={styles.inputContainer}>
                <User size={18} color="#94a3b8" style={styles.inputLeftIcon} />
                <TextInput
                  value={usernameOrNis}
                  onChangeText={(text) => {
                    setUsernameOrNis(text);
                    if (errorMessage) setErrorMessage(null);
                  }}
                  placeholder="Username atau NIS"
                  placeholderTextColor="#94a3b8"
                  autoCapitalize="none"
                  autoCorrect={false}
                  style={styles.textInput}
                />
              </View>

              {/* Input Password */}
              <View style={styles.inputContainer}>
                <Lock size={18} color="#94a3b8" style={styles.inputLeftIcon} />
                <TextInput
                  value={password}
                  onChangeText={(text) => {
                    setPassword(text);
                    if (errorMessage) setErrorMessage(null);
                  }}
                  placeholder="Password"
                  placeholderTextColor="#94a3b8"
                  secureTextEntry={!showPassword}
                  autoCapitalize="none"
                  style={styles.textInput}
                />
                <TouchableOpacity
                  onPress={() => setShowPassword(!showPassword)}
                  style={styles.eyeButton}
                  hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
                >
                  {showPassword ? (
                    <EyeOff size={18} color="#94a3b8" />
                  ) : (
                    <Eye size={18} color="#94a3b8" />
                  )}
                </TouchableOpacity>
              </View>

              {/* Tombol Masuk */}
              <TouchableOpacity
                onPress={handleLogin}
                disabled={loading}
                activeOpacity={0.88}
                style={[styles.loginButton, loading && styles.loginButtonDisabled]}
              >
                {loading ? (
                  <ActivityIndicator color="#ffffff" size="small" />
                ) : (
                  <>
                    <Text style={styles.loginButtonText}>Masuk</Text>
                    <ArrowRight size={18} color="#ffffff" style={styles.arrowIcon} />
                  </>
                )}
              </TouchableOpacity>

              {/* Slogan pemanis di bawah tombol login */}
              <View style={styles.subtextContainer}>
                <View style={styles.subtextLine} />
                <Text style={styles.subtextContent}>Absensi lebih mudah, lebih cepat</Text>
                <View style={styles.subtextLine} />
              </View>
            </View>

            {/* Bottom Hero with School Building & Crisp Royal Blue Tone */}
            <View style={styles.bottomHeroContainer}>
              <Image
                source={require('../../assets/school-building.jpg')}
                style={[StyleSheet.absoluteFill, { width: '100%', height: '100%' }]}
                resizeMode="cover"
              />
              {/* Crisp Royal Blue & Deep Navy Grading - Jernih, tidak mableng/berkabut putih */}
              <Svg height="100%" width="100%" style={StyleSheet.absoluteFill}>
                <Defs>
                  <LinearGradient id="heroGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                    <Stop offset="0%" stopColor="#021d49" stopOpacity="0.16" />
                    <Stop offset="40%" stopColor="#00337f" stopOpacity="0.40" />
                    <Stop offset="72%" stopColor="#001d4a" stopOpacity="0.76" />
                    <Stop offset="100%" stopColor="#000d24" stopOpacity="0.94" />
                  </LinearGradient>
                </Defs>
                <Rect width="100%" height="100%" fill="url(#heroGradient)" />
              </Svg>

              {/* Elegant Wave Transition di bagian atas foto gedung */}
              <View style={styles.heroTopWave} pointerEvents="none">
                <Svg
                  height={32}
                  width={SCREEN_WIDTH}
                  viewBox={`0 0 ${SCREEN_WIDTH} 32`}
                  preserveAspectRatio="none"
                >
                  <Path
                    d={`M0,0 L${SCREEN_WIDTH},0 L${SCREEN_WIDTH},12 C${SCREEN_WIDTH * 0.7},32 ${SCREEN_WIDTH * 0.3},2 0,20 Z`}
                    fill="#f8fafc"
                  />
                </Svg>
              </View>

              {/* Slogan at Bottom */}
              <View style={styles.sloganContainer}>
                <View style={styles.sloganBar} />
                <Text style={styles.sloganText}>Disiplin Hari Ini, Prestasi Esok Nanti</Text>
              </View>
            </View>
          </ScrollView>
        </KeyboardAvoidingView>

        {/* Modal Pengaturan Server Backend */}
        <Modal
          visible={serverModalVisible}
          animationType="fade"
          transparent={true}
          onRequestClose={() => setServerModalVisible(false)}
        >
          <View style={styles.modalOverlay}>
            <View style={styles.modalCard}>
              <View style={styles.modalHeader}>
                <View style={styles.modalTitleRow}>
                  <Server size={20} color="#0056b3" style={{ marginRight: 8 }} />
                  <Text style={styles.modalTitle}>Alamat Server Backend</Text>
                </View>
                <TouchableOpacity onPress={() => setServerModalVisible(false)} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}>
                  <X size={20} color="#64748b" />
                </TouchableOpacity>
              </View>

              <Text style={styles.modalSubtitle}>
                Masukkan URL API Laravel di laptop Anda. Pastikan HP dan laptop terhubung ke Wi-Fi yang sama.
              </Text>

              <View style={styles.serverInputContainer}>
                <TextInput
                  value={serverUrlInput}
                  onChangeText={setServerUrlInput}
                  placeholder="http://192.168.1.13:8000/api"
                  placeholderTextColor="#94a3b8"
                  autoCapitalize="none"
                  autoCorrect={false}
                  style={styles.serverTextInput}
                />
              </View>

              {/* Tombol Preset Cepat */}
              <View style={styles.presetContainer}>
                <TouchableOpacity
                  onPress={() => setServerUrlInput('http://192.168.1.13:8000/api')}
                  style={styles.presetChip}
                >
                  <Text style={styles.presetChipText}>Wi-Fi Laptop (192.168.1.13)</Text>
                </TouchableOpacity>
                <TouchableOpacity
                  onPress={() => setServerUrlInput('http://10.0.2.2:8000/api')}
                  style={styles.presetChip}
                >
                  <Text style={styles.presetChipText}>Emulator (10.0.2.2)</Text>
                </TouchableOpacity>
              </View>

              {/* Status Hasil Tes Koneksi */}
              {testStatus && (
                <View style={[styles.testStatusBox, testStatus.success ? styles.testStatusSuccess : styles.testStatusFail]}>
                  {testStatus.loading ? (
                    <ActivityIndicator size="small" color="#0056b3" />
                  ) : testStatus.success ? (
                    <CheckCircle2 size={16} color="#16a34a" style={{ marginRight: 6 }} />
                  ) : (
                    <AlertCircle size={16} color="#dc2626" style={{ marginRight: 6 }} />
                  )}
                  <Text style={[styles.testStatusText, testStatus.success ? styles.testTextSuccess : styles.testTextFail]}>
                    {testStatus.loading ? 'Sedang mengetes koneksi...' : testStatus.message}
                  </Text>
                </View>
              )}

              {/* Action Buttons */}
              <View style={styles.modalActions}>
                <TouchableOpacity
                  onPress={handleTestConnection}
                  disabled={testStatus?.loading}
                  style={styles.testButton}
                >
                  <RefreshCw size={15} color="#0056b3" style={{ marginRight: 6 }} />
                  <Text style={styles.testButtonText}>Tes Koneksi</Text>
                </TouchableOpacity>

                <TouchableOpacity
                  onPress={handleSaveServerUrl}
                  style={styles.saveButton}
                >
                  <Text style={styles.saveButtonText}>Simpan & Terapkan</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>
        </Modal>
      </View>
    </TouchableWithoutFeedback>
  );
}

const styles = StyleSheet.create({
  rootContainer: {
    flex: 1,
  },
  topWaveContainer: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: 180,
    zIndex: 0,
  },
  keyboardContainer: {
    flex: 1,
    zIndex: 1,
  },
  scrollContent: {
    flexGrow: 1,
    justifyContent: 'space-between',
    paddingTop: Platform.OS === 'ios' ? 56 : 46,
    paddingBottom: 0,
  },
  headerSection: {
    alignItems: 'center',
    paddingHorizontal: 24,
    position: 'relative',
    width: '100%',
  },
  serverSettingsBtn: {
    position: 'absolute',
    top: 0,
    right: 24,
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: '#eff6ff',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: '#bfdbfe',
    zIndex: 10,
  },
  logoImage: {
    width: 64,
    height: 64,
    marginBottom: 8,
  },
  aiWelcomeBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 7,
  },
  aiWelcomeDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: '#22d3ee',
    marginRight: 6,
  },
  aiWelcomeText: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 8,
    letterSpacing: 1.2,
    color: '#0891b2',
  },
  schoolNameLine1: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 12,
    color: '#0056b3',
    letterSpacing: 1.5,
    textTransform: 'uppercase',
  },
  schoolNameLine2: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 13,
    color: '#0f172a',
    letterSpacing: 1.5,
    textTransform: 'uppercase',
    marginTop: 1,
  },
  welcomeTitle: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 24,
    color: '#0f172a',
    marginTop: 18,
    textAlign: 'center',
  },
  welcomeSubtitle: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 13,
    color: '#64748b',
    marginTop: 5,
    textAlign: 'center',
  },
  formSection: {
    marginHorizontal: 20,
    paddingHorizontal: 20,
    paddingTop: 18,
    paddingBottom: 18,
    marginTop: 20,
    marginBottom: 16,
    borderRadius: 24,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: '#e8eef7',
    shadowColor: '#1e3a8a',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.10,
    shadowRadius: 24,
    elevation: 8,
  },
  errorAlert: {
    backgroundColor: '#fee2e2',
    borderWidth: 1,
    borderColor: '#fca5a5',
    borderRadius: 12,
    paddingVertical: 10,
    paddingHorizontal: 14,
    marginBottom: 14,
  },
  errorText: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 12,
    color: '#b91c1c',
    textAlign: 'center',
  },
  errorHint: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 11,
    color: '#991b1b',
    textAlign: 'center',
    marginTop: 4,
    textDecorationLine: 'underline',
  },
  inputContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    height: 48,
    backgroundColor: '#ffffff',
    borderWidth: 1.2,
    borderColor: '#e2e8f0',
    borderRadius: 12,
    paddingHorizontal: 14,
    marginBottom: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.03,
    shadowRadius: 3,
    elevation: 1,
  },
  inputLeftIcon: {
    marginRight: 10,
  },
  textInput: {
    flex: 1,
    fontFamily: Typography.fontFamily.regular,
    fontSize: 14,
    color: '#0f172a',
    height: '100%',
  },
  eyeButton: {
    padding: 6,
  },
  loginButton: {
    height: 48,
    backgroundColor: '#0056b3',
    borderRadius: 12,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 6,
    shadowColor: '#0056b3',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.28,
    shadowRadius: 10,
    elevation: 4,
  },
  loginButtonDisabled: {
    opacity: 0.7,
  },
  loginButtonText: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 15,
    color: '#ffffff',
    marginRight: 6,
  },
  arrowIcon: {
    marginLeft: 2,
  },
  subtextContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 18,
    marginBottom: 4,
  },
  subtextLine: {
    flex: 1,
    height: 1,
    backgroundColor: '#e2e8f0',
  },
  subtextContent: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 11,
    color: '#94a3b8',
    marginHorizontal: 12,
    letterSpacing: 0.2,
  },
  bottomHeroContainer: {
    width: '100%',
    height: Math.max(260, Math.min(320, SCREEN_HEIGHT * 0.33)),
    marginTop: 'auto',
    overflow: 'hidden',
    position: 'relative',
    justifyContent: 'flex-end',
  },
  heroTopWave: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: 32,
    zIndex: 2,
  },
  bottomHeroImage: {
    width: '100%',
    height: '100%',
    justifyContent: 'flex-end',
  },
  bottomHeroImageStyle: {
    borderTopLeftRadius: 0,
    borderTopRightRadius: 0,
  },
  sloganContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 20,
    paddingBottom: Platform.OS === 'ios' ? 28 : 20,
    zIndex: 3,
  },
  sloganBar: {
    width: 22,
    height: 2.5,
    backgroundColor: '#38bdf8',
    marginRight: 8,
    borderRadius: 2,
  },
  sloganText: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 12,
    color: '#ffffff',
    letterSpacing: 0.4,
    textShadowColor: 'rgba(0, 0, 0, 0.4)',
    textShadowOffset: { width: 0, height: 1 },
    textShadowRadius: 3,
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

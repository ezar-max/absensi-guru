import React, { useState } from 'react';
import {
  View,
  Text,
  Modal,
  TouchableOpacity,
  TextInput,
  StyleSheet,
  KeyboardAvoidingView,
  Platform,
  TouchableWithoutFeedback,
  ScrollView,
} from 'react-native';
import { X, UserX, FileText, HeartPulse, Briefcase, AlertTriangle, CheckCircle } from 'lucide-react-native';
import { useTheme } from '../context/ThemeContext';
import { Radius, Spacing, Typography } from '../constants/theme';
import { SlotData } from './SlotCard';

interface ActionBottomSheetProps {
  visible: boolean;
  slot: SlotData | null;
  serverTime: string;
  onClose: () => void;
  onSubmit: (action: string, note?: string) => Promise<void>;
  loading?: boolean;
}

export const ActionBottomSheet: React.FC<ActionBottomSheetProps> = ({
  visible,
  slot,
  serverTime,
  onClose,
  onSubmit,
  loading = false,
}) => {
  const { colors } = useTheme();
  const [selectedAction, setSelectedAction] = useState<string>('izin');
  const [note, setNote] = useState<string>('');
  const [showConfirmKosong, setShowConfirmKosong] = useState<boolean>(false);

  if (!slot) {
    return null;
  }

  // Hitung apakah opsi kosong sudah diizinkan (>= 50% durasi jam pelajaran)
  const serverDate = new Date(serverTime);
  const currentMinutes = serverDate.getHours() * 60 + serverDate.getMinutes();

  const [startH, startM] = slot.start.split(':').map(Number);
  const [endH, endM] = slot.end.split(':').map(Number);
  const startMinutes = startH * 60 + startM;
  const endMinutes = endH * 60 + endM;
  const duration = endMinutes - startMinutes;
  const thresholdMinutes = Math.ceil(duration * 0.5);
  const allowedKosongMinutes = startMinutes + thresholdMinutes;

  const isKosongAllowed = currentMinutes >= allowedKosongMinutes;

  const allowedKosongHour = Math.floor(allowedKosongMinutes / 60);
  const allowedKosongMin = allowedKosongMinutes % 60;
  const allowedKosongTimeStr = `${String(allowedKosongHour).padStart(2, '0')}:${String(allowedKosongMin).padStart(2, '0')}`;

  const handlePressSubmit = () => {
    if (selectedAction === 'kosong') {
      setShowConfirmKosong(true);
    } else {
      onSubmit(selectedAction, note.trim() || undefined);
    }
  };

  const handleConfirmKosong = () => {
    setShowConfirmKosong(false);
    onSubmit('kosong', note.trim() || undefined);
  };

  return (
    <Modal
      visible={visible}
      transparent
      animationType="slide"
      onRequestClose={onClose}
    >
      <TouchableWithoutFeedback onPress={onClose}>
        <View style={styles.backdrop}>
          <TouchableWithoutFeedback>
            <KeyboardAvoidingView
              behavior={Platform.OS === 'ios' ? 'padding' : undefined}
              style={[styles.sheetContainer, { backgroundColor: colors.surface }]}
            >
              {/* Drag Handle Bar */}
              <View style={styles.handleContainer}>
                <View style={styles.handleBar} />
              </View>

              {/* Header */}
              <View style={styles.sheetHeader}>
                <View style={{ flex: 1 }}>
                  <Text style={[styles.sheetTitle, { color: colors.text }]}>
                    Catat Status Kehadiran
                  </Text>
                  <Text style={[styles.sheetSubtitle, { color: colors.textMuted }]} numberOfLines={1}>
                    {slot.subject} — {slot.teacher}
                  </Text>
                </View>
                <TouchableOpacity onPress={onClose} style={styles.closeBtn} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}>
                  <X size={20} color="#64748b" />
                </TouchableOpacity>
              </View>

              {/* Opsi Status */}
              <View style={styles.optionsList}>
                {/* 1. Kosong (Guru Tidak Datang) */}
                <TouchableOpacity
                  activeOpacity={0.7}
                  disabled={!isKosongAllowed}
                  onPress={() => setSelectedAction('kosong')}
                  style={[
                    styles.optionItem,
                    {
                      borderColor: selectedAction === 'kosong' ? '#ef4444' : '#e2e8f0',
                      backgroundColor: selectedAction === 'kosong' ? '#fef2f2' : '#f8fafc',
                      opacity: isKosongAllowed ? 1 : 0.5,
                    },
                  ]}
                >
                  <View style={[styles.iconWrap, { backgroundColor: '#fee2e2' }]}>
                    <UserX size={18} color="#ef4444" />
                  </View>
                  <View style={styles.optionContent}>
                    <Text style={[styles.optionLabel, { color: '#0f172a' }]}>
                      Guru Tidak Datang (Kosong)
                    </Text>
                    <Text style={[styles.optionHint, { color: '#64748b' }]}>
                      {isKosongAllowed
                        ? 'Dapat dicatat (sudah melewati 50% jam pelajaran)'
                        : `Bisa dipilih mulai pukul ${allowedKosongTimeStr}`}
                    </Text>
                  </View>
                  {selectedAction === 'kosong' && (
                    <CheckCircle size={18} color="#ef4444" />
                  )}
                </TouchableOpacity>

                {/* 2. Izin */}
                <TouchableOpacity
                  activeOpacity={0.7}
                  onPress={() => setSelectedAction('izin')}
                  style={[
                    styles.optionItem,
                    {
                      borderColor: selectedAction === 'izin' ? '#0056b3' : '#e2e8f0',
                      backgroundColor: selectedAction === 'izin' ? '#eff6ff' : '#f8fafc',
                    },
                  ]}
                >
                  <View style={[styles.iconWrap, { backgroundColor: '#dbeafe' }]}>
                    <FileText size={18} color="#0056b3" />
                  </View>
                  <View style={styles.optionContent}>
                    <Text style={[styles.optionLabel, { color: '#0f172a' }]}>
                      Izin
                    </Text>
                    <Text style={[styles.optionHint, { color: '#64748b' }]}>
                      Guru berhalangan hadir dengan surat keterangan izin
                    </Text>
                  </View>
                  {selectedAction === 'izin' && (
                    <CheckCircle size={18} color="#0056b3" />
                  )}
                </TouchableOpacity>

                {/* 3. Sakit */}
                <TouchableOpacity
                  activeOpacity={0.7}
                  onPress={() => setSelectedAction('sakit')}
                  style={[
                    styles.optionItem,
                    {
                      borderColor: selectedAction === 'sakit' ? '#7c3aed' : '#e2e8f0',
                      backgroundColor: selectedAction === 'sakit' ? '#f5f3ff' : '#f8fafc',
                    },
                  ]}
                >
                  <View style={[styles.iconWrap, { backgroundColor: '#ede9fe' }]}>
                    <HeartPulse size={18} color="#7c3aed" />
                  </View>
                  <View style={styles.optionContent}>
                    <Text style={[styles.optionLabel, { color: '#0f172a' }]}>
                      Sakit
                    </Text>
                    <Text style={[styles.optionHint, { color: '#64748b' }]}>
                      Guru berhalangan hadir karena sakit
                    </Text>
                  </View>
                  {selectedAction === 'sakit' && (
                    <CheckCircle size={18} color="#7c3aed" />
                  )}
                </TouchableOpacity>

                {/* 4. Dinas Luar */}
                <TouchableOpacity
                  activeOpacity={0.7}
                  onPress={() => setSelectedAction('dinas')}
                  style={[
                    styles.optionItem,
                    {
                      borderColor: selectedAction === 'dinas' ? '#0284c7' : '#e2e8f0',
                      backgroundColor: selectedAction === 'dinas' ? '#f0f9ff' : '#f8fafc',
                    },
                  ]}
                >
                  <View style={[styles.iconWrap, { backgroundColor: '#e0f2fe' }]}>
                    <Briefcase size={18} color="#0284c7" />
                  </View>
                  <View style={styles.optionContent}>
                    <Text style={[styles.optionLabel, { color: '#0f172a' }]}>
                      Tugas Dinas Luar
                    </Text>
                    <Text style={[styles.optionHint, { color: '#64748b' }]}>
                      Tugas resmi, workshop, atau dinas luar sekolah
                    </Text>
                  </View>
                  {selectedAction === 'dinas' && (
                    <CheckCircle size={18} color="#0284c7" />
                  )}
                </TouchableOpacity>
              </View>

              {/* Kolom Catatan Opsional */}
              <View style={styles.inputContainer}>
                <Text style={[styles.inputLabel, { color: colors.text }]}>
                  Catatan Keterangan (Opsional):
                </Text>
                <TextInput
                  value={note}
                  onChangeText={setNote}
                  placeholder="Misal: Tugas dikirimkan via ketua kelas..."
                  placeholderTextColor="#94a3b8"
                  style={[
                    styles.textInput,
                    {
                      color: colors.text,
                      backgroundColor: '#f8fafc',
                      borderColor: '#e2e8f0',
                    },
                  ]}
                  multiline
                  numberOfLines={2}
                />
              </View>

              {/* Tombol Simpan */}
              <TouchableOpacity
                activeOpacity={0.88}
                disabled={loading}
                onPress={handlePressSubmit}
                style={[styles.submitButton, { backgroundColor: '#0056b3' }]}
              >
                <Text style={styles.submitButtonText}>
                  {loading ? 'Menyimpan...' : 'Kirim Catatan Kehadiran'}
                </Text>
              </TouchableOpacity>

              {/* Modal Konfirmasi Kosong */}
              {showConfirmKosong && (
                <View style={styles.confirmOverlay}>
                  <View style={[styles.confirmBox, { backgroundColor: colors.surface }]}>
                    <AlertTriangle size={32} color="#dc2626" style={{ marginBottom: 12 }} />
                    <Text style={[styles.confirmTitle, { color: colors.text }]}>
                      Konfirmasi Guru Kosong
                    </Text>
                    <Text style={[styles.confirmDesc, { color: colors.textMuted }]}>
                      Apakah Anda yakin guru bersangkutan benar-benar tidak hadir pada jam ini? Laporan ini akan langsung diteruskan ke bagian kurikulum & piket.
                    </Text>
                    <View style={styles.confirmActionRow}>
                      <TouchableOpacity
                        onPress={() => setShowConfirmKosong(false)}
                        style={styles.confirmCancelBtn}
                      >
                        <Text style={styles.confirmCancelText}>Batal</Text>
                      </TouchableOpacity>
                      <TouchableOpacity
                        onPress={handleConfirmKosong}
                        style={styles.confirmOkBtn}
                      >
                        <Text style={styles.confirmOkText}>Ya, Kosong</Text>
                      </TouchableOpacity>
                    </View>
                  </View>
                </View>
              )}
            </KeyboardAvoidingView>
          </TouchableWithoutFeedback>
        </View>
      </TouchableWithoutFeedback>
    </Modal>
  );
};

const styles = StyleSheet.create({
  backdrop: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.65)',
    justifyContent: 'flex-end',
  },
  sheetContainer: {
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    padding: Spacing.xl,
    paddingTop: 12,
    paddingBottom: Platform.OS === 'ios' ? 36 : 24,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: -4 },
    shadowOpacity: 0.12,
    shadowRadius: 16,
    elevation: 10,
  },
  handleContainer: {
    alignItems: 'center',
    marginBottom: 10,
  },
  handleBar: {
    width: 36,
    height: 4,
    borderRadius: 2,
    backgroundColor: '#cbd5e1',
  },
  sheetHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: Spacing.md,
  },
  sheetTitle: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 17,
  },
  sheetSubtitle: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 12,
    marginTop: 2,
  },
  closeBtn: {
    padding: 6,
  },
  optionsList: {
    gap: 8,
    marginVertical: Spacing.xs,
  },
  optionItem: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 11,
    borderRadius: Radius.card,
    borderWidth: 1.2,
    gap: 10,
  },
  iconWrap: {
    width: 34,
    height: 34,
    borderRadius: 17,
    alignItems: 'center',
    justifyContent: 'center',
  },
  optionContent: {
    flex: 1,
  },
  optionLabel: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 13,
  },
  optionHint: {
    fontFamily: Typography.fontFamily.regular,
    fontSize: 11,
    marginTop: 1,
  },
  inputContainer: {
    marginTop: Spacing.sm,
  },
  inputLabel: {
    fontFamily: Typography.fontFamily.semiBold,
    fontSize: 12,
    marginBottom: 5,
  },
  textInput: {
    borderWidth: 1,
    borderRadius: Radius.input,
    paddingHorizontal: 12,
    paddingVertical: 8,
    fontSize: 12,
    fontFamily: Typography.fontFamily.regular,
    minHeight: 48,
    textAlignVertical: 'top',
  },
  submitButton: {
    height: 46,
    borderRadius: Radius.button,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: Spacing.md,
    shadowColor: '#0056b3',
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.22,
    shadowRadius: 6,
    elevation: 3,
  },
  submitButtonText: {
    color: '#FFFFFF',
    fontFamily: Typography.fontFamily.bold,
    fontSize: 14,
  },
  confirmOverlay: {
    ...StyleSheet.absoluteFill,
    backgroundColor: 'rgba(15, 23, 42, 0.75)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: Spacing.xl,
    borderRadius: 24,
  },
  confirmBox: {
    width: '100%',
    padding: 20,
    borderRadius: Radius.card,
    alignItems: 'center',
  },
  confirmTitle: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 16,
    textAlign: 'center',
  },
  confirmDesc: {
    fontFamily: Typography.fontFamily.regular,
    fontSize: 12,
    textAlign: 'center',
    marginVertical: 12,
    lineHeight: 18,
  },
  confirmActionRow: {
    flexDirection: 'row',
    gap: 10,
    width: '100%',
    marginTop: 6,
  },
  confirmCancelBtn: {
    flex: 1,
    height: 42,
    borderRadius: Radius.button,
    backgroundColor: '#f1f5f9',
    alignItems: 'center',
    justifyContent: 'center',
  },
  confirmCancelText: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 13,
    color: '#475569',
  },
  confirmOkBtn: {
    flex: 1,
    height: 42,
    borderRadius: Radius.button,
    backgroundColor: '#dc2626',
    alignItems: 'center',
    justifyContent: 'center',
  },
  confirmOkText: {
    color: '#FFFFFF',
    fontFamily: Typography.fontFamily.bold,
    fontSize: 13,
  },
});

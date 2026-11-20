import React, { useState } from 'react';
import {
  View,
  Text,
  Modal,
  TouchableOpacity,
  TextInput,
  StyleSheet,
  Alert,
  KeyboardAvoidingView,
  Platform,
  TouchableWithoutFeedback,
} from 'react-native';
import { X, UserX, FileText, HeartPulse, Briefcase, AlertTriangle } from 'lucide-react-native';
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
              {/* Header */}
              <View style={styles.sheetHeader}>
                <View>
                  <Text style={[styles.sheetTitle, { color: colors.text }]}>
                    Catat Status Kehadiran
                  </Text>
                  <Text style={[styles.sheetSubtitle, { color: colors.textMuted }]}>
                    {slot.subject} — {slot.teacher}
                  </Text>
                </View>
                <TouchableOpacity onPress={onClose} style={styles.closeBtn}>
                  <X size={20} color={colors.textMuted} />
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
                      borderColor: selectedAction === 'kosong' ? colors.danger : colors.border,
                      backgroundColor: selectedAction === 'kosong' ? colors.dangerSoft : colors.bg,
                      opacity: isKosongAllowed ? 1 : 0.5,
                    },
                  ]}
                >
                  <UserX size={20} color={colors.danger} />
                  <View style={styles.optionContent}>
                    <Text style={[styles.optionLabel, { color: colors.text }]}>
                      Guru Tidak Datang (Kosong)
                    </Text>
                    <Text style={[styles.optionHint, { color: colors.textMuted }]}>
                      {isKosongAllowed
                        ? 'Dapat dipilih karena sudah melewati 50% jam pelajaran'
                        : `Baru bisa dipilih mulai pukul ${allowedKosongTimeStr}`}
                    </Text>
                  </View>
                </TouchableOpacity>

                {/* 2. Izin */}
                <TouchableOpacity
                  activeOpacity={0.7}
                  onPress={() => setSelectedAction('izin')}
                  style={[
                    styles.optionItem,
                    {
                      borderColor: selectedAction === 'izin' ? colors.info : colors.border,
                      backgroundColor: selectedAction === 'izin' ? colors.infoSoft : colors.bg,
                    },
                  ]}
                >
                  <FileText size={20} color={colors.info} />
                  <View style={styles.optionContent}>
                    <Text style={[styles.optionLabel, { color: colors.text }]}>
                      Izin
                    </Text>
                    <Text style={[styles.optionHint, { color: colors.textMuted }]}>
                      Guru berhalangan hadir dengan surat keterangan izin
                    </Text>
                  </View>
                </TouchableOpacity>

                {/* 3. Sakit */}
                <TouchableOpacity
                  activeOpacity={0.7}
                  onPress={() => setSelectedAction('sakit')}
                  style={[
                    styles.optionItem,
                    {
                      borderColor: selectedAction === 'sakit' ? colors.info : colors.border,
                      backgroundColor: selectedAction === 'sakit' ? colors.infoSoft : colors.bg,
                    },
                  ]}
                >
                  <HeartPulse size={20} color={colors.info} />
                  <View style={styles.optionContent}>
                    <Text style={[styles.optionLabel, { color: colors.text }]}>
                      Sakit
                    </Text>
                    <Text style={[styles.optionHint, { color: colors.textMuted }]}>
                      Guru berhalangan hadir karena sakit
                    </Text>
                  </View>
                </TouchableOpacity>

                {/* 4. Dinas */}
                <TouchableOpacity
                  activeOpacity={0.7}
                  onPress={() => setSelectedAction('dinas')}
                  style={[
                    styles.optionItem,
                    {
                      borderColor: selectedAction === 'dinas' ? colors.info : colors.border,
                      backgroundColor: selectedAction === 'dinas' ? colors.infoSoft : colors.bg,
                    },
                  ]}
                >
                  <Briefcase size={20} color={colors.info} />
                  <View style={styles.optionContent}>
                    <Text style={[styles.optionLabel, { color: colors.text }]}>
                      Dinas Luar
                    </Text>
                    <Text style={[styles.optionHint, { color: colors.textMuted }]}>
                      Tugas resmi, workshop, atau dinas luar sekolah
                    </Text>
                  </View>
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
                  placeholder="Misal: Tugas dikirim via ketua kelas..."
                  placeholderTextColor={colors.textMuted}
                  style={[
                    styles.textInput,
                    {
                      color: colors.text,
                      backgroundColor: colors.bg,
                      borderColor: colors.border,
                    },
                  ]}
                  multiline
                  numberOfLines={2}
                />
              </View>

              {/* Tombol Simpan */}
              <TouchableOpacity
                activeOpacity={0.85}
                disabled={loading}
                onPress={handlePressSubmit}
                style={[styles.submitButton, { backgroundColor: colors.primary }]}
              >
                <Text style={styles.submitButtonText}>
                  {loading ? 'Menyimpan...' : 'Kirim Catatan Kehadiran'}
                </Text>
              </TouchableOpacity>

              {/* Modal Konfirmasi Kosong */}
              {showConfirmKosong && (
                <View style={styles.confirmOverlay}>
                  <View style={[styles.confirmBox, { backgroundColor: colors.surface }]}>
                    <AlertTriangle size={32} color={colors.danger} style={{ marginBottom: 12 }} />
                    <Text style={[styles.confirmTitle, { color: colors.text }]}>
                      Konfirmasi Guru Kosong
                    </Text>
                    <Text style={[styles.confirmDesc, { color: colors.textMuted }]}>
                      Apakah Anda yakin guru bersangkutan benar-benar tidak hadir pada jam ini? Laporan ini akan langsung diteruskan ke piket & kurikulum.
                    </Text>
                    <View style={styles.confirmActionRow}>
                      <TouchableOpacity
                        onPress={() => setShowConfirmKosong(false)}
                        style={[styles.confirmCancelBtn, { backgroundColor: colors.neutralSoft }]}
                      >
                        <Text style={[styles.confirmCancelText, { color: colors.text }]}>Batal</Text>
                      </TouchableOpacity>
                      <TouchableOpacity
                        onPress={handleConfirmKosong}
                        style={[styles.confirmOkBtn, { backgroundColor: colors.danger }]}
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
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'flex-end',
  },
  sheetContainer: {
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    padding: Spacing.xl,
    paddingBottom: Platform.OS === 'ios' ? 36 : 24,
  },
  sheetHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: Spacing.md,
  },
  sheetTitle: {
    fontFamily: Typography.fontFamily.bold,
    fontSize: 18,
  },
  sheetSubtitle: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 13,
    marginTop: 2,
  },
  closeBtn: {
    padding: 6,
  },
  optionsList: {
    gap: 8,
    marginVertical: Spacing.sm,
  },
  optionItem: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
    borderRadius: Radius.card,
    borderWidth: 1.5,
    gap: 12,
  },
  optionContent: {
    flex: 1,
  },
  optionLabel: {
    fontFamily: Typography.fontFamily.semiBold,
    fontSize: 14,
  },
  optionHint: {
    fontFamily: Typography.fontFamily.regular,
    fontSize: 11,
    marginTop: 2,
  },
  inputContainer: {
    marginTop: Spacing.md,
  },
  inputLabel: {
    fontFamily: Typography.fontFamily.medium,
    fontSize: 13,
    marginBottom: 6,
  },
  textInput: {
    borderWidth: 1,
    borderRadius: Radius.input,
    paddingHorizontal: 12,
    paddingVertical: 8,
    fontSize: 13,
    fontFamily: Typography.fontFamily.regular,
    minHeight: 52,
    textAlignVertical: 'top',
  },
  submitButton: {
    height: 52,
    borderRadius: Radius.button,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: Spacing.lg,
  },
  submitButtonText: {
    color: '#FFFFFF',
    fontFamily: Typography.fontFamily.bold,
    fontSize: 15,
  },
  confirmOverlay: {
    ...StyleSheet.absoluteFill,
    backgroundColor: 'rgba(0,0,0,0.7)',
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
    fontSize: 13,
    textAlign: 'center',
    marginVertical: 12,
    lineHeight: 18,
  },
  confirmActionRow: {
    flexDirection: 'row',
    gap: 12,
    width: '100%',
    marginTop: 8,
  },
  confirmCancelBtn: {
    flex: 1,
    height: 44,
    borderRadius: Radius.button,
    alignItems: 'center',
    justifyContent: 'center',
  },
  confirmCancelText: {
    fontFamily: Typography.fontFamily.semiBold,
    fontSize: 14,
  },
  confirmOkBtn: {
    flex: 1,
    height: 44,
    borderRadius: Radius.button,
    alignItems: 'center',
    justifyContent: 'center',
  },
  confirmOkText: {
    color: '#FFFFFF',
    fontFamily: Typography.fontFamily.bold,
    fontSize: 14,
  },
});

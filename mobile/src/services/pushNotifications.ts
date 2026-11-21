import { Platform } from 'react-native';
import * as Notifications from 'expo-notifications';
import * as SecureStore from 'expo-secure-store';
import Constants, { AppOwnership } from 'expo-constants';
import { api } from './api';

const DEVICE_TOKEN_KEY = 'absensi_fcm_device_token';

export async function registerPushDevice(): Promise<void> {
  if (Platform.OS !== 'android') return;
  if (Constants.appOwnership === AppOwnership.Expo) {
    console.warn('Push notification memerlukan development build; Expo Go Android tidak mendukung push remote.');
    return;
  }

  await Notifications.setNotificationChannelAsync('reminders', {
    name: 'Pengingat',
    importance: Notifications.AndroidImportance.HIGH,
    sound: null,
    enableVibrate: false,
  });

  const permissions = await Notifications.getPermissionsAsync();
  const status = permissions.granted
    ? permissions.status
    : (await Notifications.requestPermissionsAsync()).status;

  if (status !== 'granted') return;

  const deviceToken = await Notifications.getDevicePushTokenAsync();
  if (deviceToken.type !== 'android') {
    throw new Error('FCM token Android tidak tersedia.');
  }

  const previousToken = await SecureStore.getItemAsync(DEVICE_TOKEN_KEY);
  await api.post('/notifications/device-token', {
    token: deviceToken.data,
    platform: 'android',
  });
  if (previousToken && previousToken !== deviceToken.data) {
    await api.delete('/notifications/device-token', { data: { token: previousToken } });
  }
  await SecureStore.setItemAsync(DEVICE_TOKEN_KEY, deviceToken.data);
}

export async function unregisterPushDevice(): Promise<void> {
  if (Platform.OS !== 'android') return;

  const token = await SecureStore.getItemAsync(DEVICE_TOKEN_KEY);
  if (!token) return;

  await api.delete('/notifications/device-token', { data: { token } });
  await SecureStore.deleteItemAsync(DEVICE_TOKEN_KEY);
}

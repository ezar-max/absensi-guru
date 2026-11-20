import axios from 'axios';
import * as SecureStore from 'expo-secure-store';

const DEFAULT_API_URL = 'http://192.168.1.8:8000/api';

let initialUrl = process.env.EXPO_PUBLIC_API_URL || DEFAULT_API_URL;
// Otomatis arahkan ke IP laptop jika bundle masih berisi 10.0.2.2 (loopback emulator)
if (initialUrl.includes('10.0.2.2')) {
  initialUrl = DEFAULT_API_URL;
}

export const API_BASE_URL = initialUrl;
export const SERVER_URL_KEY = 'absensi_server_url';

export const TOKEN_KEY = 'absensi_auth_token';
export const USER_KEY = 'absensi_auth_user';
export const CLASSES_KEY = 'absensi_auth_classes';

export const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  timeout: 10000,
});

api.interceptors.request.use(async (config) => {
  try {
    const customUrl = await SecureStore.getItemAsync(SERVER_URL_KEY);
    if (customUrl && customUrl.trim().length > 0) {
      config.baseURL = customUrl.trim();
    } else {
      config.baseURL = API_BASE_URL;
    }

    const token = await SecureStore.getItemAsync(TOKEN_KEY);
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
  } catch (error) {
    console.error('Gagal mengambil konfigurasi auth/server:', error);
  }
  return config;
});

// Listener untuk 401 Unauthorized
let unauthorizedCallback: (() => void) | null = null;

export const setUnauthorizedCallback = (callback: () => void) => {
  unauthorizedCallback = callback;
};

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      if (unauthorizedCallback) {
        unauthorizedCallback();
      }
    }
    return Promise.reject(error);
  }
);

export const getErrorMessage = (error: any): string => {
  if (!error.response) {
    const target = error.config?.baseURL || API_BASE_URL;
    return `Gagal terhubung ke server (${target}). Pastikan HP & Laptop di Wi-Fi yang sama.`;
  }

  const data = error.response.data;

  // Cek apakah ada format errors.message[0]
  if (data?.errors?.message && Array.isArray(data.errors.message) && data.errors.message.length > 0) {
    return data.errors.message[0];
  }

  if (data?.message) {
    return data.message;
  }

  return 'Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.';
};


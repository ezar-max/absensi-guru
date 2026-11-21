import React, { createContext, useContext, useEffect, useRef, useState } from 'react';
import { AppState } from 'react-native';
import * as SecureStore from 'expo-secure-store';
import { api, setUnauthorizedCallback, TOKEN_KEY, USER_KEY, CLASSES_KEY } from '../services/api';
import { registerPushDevice, unregisterPushDevice } from '../services/pushNotifications';

export interface User {
  id: number;
  name: string;
  nis?: string;
  email?: string;
  role: string;
}

export interface SchoolClass {
  id: number;
  name: string;
}

interface AuthContextType {
  user: User | null;
  classes: SchoolClass[];
  token: string | null;
  isLoading: boolean;
  login: (email: string, password: string, device?: string) => Promise<void>;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType>({
  user: null,
  classes: [],
  token: null,
  isLoading: true,
  login: async () => {},
  logout: async () => {},
});

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [classes, setClasses] = useState<SchoolClass[]>([]);
  const [token, setToken] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const logoutInProgress = useRef(false);

  const logout = async () => {
    if (logoutInProgress.current) return;
    logoutInProgress.current = true;

    try {
      if (token) {
        try {
          await unregisterPushDevice();
        } catch (error) {
          console.error('Gagal menghapus perangkat dari pengingat push:', error);
        }
        await api.post('/logout').catch((error) => {
          console.error('Gagal mencabut sesi di server:', error);
        });
      }
    } finally {
      await SecureStore.deleteItemAsync(TOKEN_KEY).catch(() => {});
      await SecureStore.deleteItemAsync(USER_KEY).catch(() => {});
      await SecureStore.deleteItemAsync(CLASSES_KEY).catch(() => {});
      setUser(null);
      setClasses([]);
      setToken(null);
      logoutInProgress.current = false;
    }
  };

  useEffect(() => {
    setUnauthorizedCallback(logout);
  }, [token]);

  useEffect(() => {
    if (!user || !token) return;

    const registerDevice = () => {
      void registerPushDevice().catch((error) => {
        console.error('Gagal mendaftarkan perangkat untuk pengingat push:', error);
      });
    };

    registerDevice();
    const subscription = AppState.addEventListener('change', (state) => {
      if (state === 'active') registerDevice();
    });

    return () => subscription.remove();
  }, [user?.id, token]);

  useEffect(() => {
    const loadStoredAuth = async () => {
      try {
        const storedToken = await SecureStore.getItemAsync(TOKEN_KEY);
        const storedUser = await SecureStore.getItemAsync(USER_KEY);
        const storedClasses = await SecureStore.getItemAsync(CLASSES_KEY);

        if (storedToken && storedUser) {
          setToken(storedToken);
          setUser(JSON.parse(storedUser));
          if (storedClasses) {
            setClasses(JSON.parse(storedClasses));
          }
        }
      } catch (e) {
        console.error('Gagal membaca sesi pengguna:', e);
      } finally {
        setIsLoading(false);
      }
    };

    loadStoredAuth();
  }, []);

  const login = async (email: string, password: string, device?: string) => {
    const response = await api.post('/login', { email, password, device });
    const { token: receivedToken, user: receivedUser, classes: receivedClasses } = response.data;

    await SecureStore.setItemAsync(TOKEN_KEY, receivedToken);
    await SecureStore.setItemAsync(USER_KEY, JSON.stringify(receivedUser));
    await SecureStore.setItemAsync(CLASSES_KEY, JSON.stringify(receivedClasses || []));

    setToken(receivedToken);
    setUser(receivedUser);
    setClasses(receivedClasses || []);
  };

  return (
    <AuthContext.Provider value={{ user, classes, token, isLoading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => useContext(AuthContext);


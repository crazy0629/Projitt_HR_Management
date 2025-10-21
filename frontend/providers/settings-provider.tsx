'use client';

/* eslint-disable @typescript-eslint/no-explicit-any */
import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from 'react';
import { APP_SETTINGS } from '@/config/settings.config';
import { Settings } from '@/config/types';

type Path = string;

type SettingsContextType = {
  /**
   * Get a setting value by its path.
   * @template T
   * @param {Path} path - Dot-separated path to the setting.
   * @returns {T} The setting value.
   */
  getOption: <T = any>(path: Path) => T;

  /**
   * Set a setting value locally (state only).
   * @template T
   * @param {Path} path - Dot-separated path to the setting.
   * @param {T} value - New value to set.
   */
  setOption: <T = any>(path: Path, value: T) => void;

  /**
   * Set a setting value and persist it to localStorage.
   * @template T
   * @param {Path} path - Dot-separated path to the setting.
   * @param {T} value - New value to store.
   */
  storeOption: <T = any>(path: Path, value: T) => void;

  /** Current settings state */
  settings: Settings;
};

const SettingsContext = createContext<SettingsContextType | undefined>(
  undefined
);

const LOCAL_STORAGE_PREFIX = 'app_settings_';

// Utility to check if code is running in browser environment
const isBrowser = () => typeof window !== 'undefined';

/**
 * Get nested value from an object by dot-separated path.
 * @param {any} obj - Object to query.
 * @param {string} path - Dot-separated path string.
 * @returns {any} Value at the path or undefined.
 */
function getFromPath(obj: any, path: string): any {
  return path.split('.').reduce((acc, part) => acc?.[part], obj);
}

/**
 * Set nested value on an object by dot-separated path immutably.
 * @param {any} obj - Object to set value on.
 * @param {string} path - Dot-separated path string.
 * @param {any} value - Value to set.
 * @returns {Settings} New object with updated value.
 */
function setToPath(obj: any, path: string, value: any): Settings {
  const keys = path.split('.');
  const lastKey = keys.pop()!;
  const lastObj = keys.reduce((acc, key) => (acc[key] ??= {}), obj);
  lastObj[lastKey] = value;
  return { ...obj };
}

/**
 * Store a single leaf value to localStorage with prefix.
 * @param {string} path - Key path.
 * @param {unknown} value - Value to store.
 */
function storeLeaf(path: string, value: unknown) {
  if (!isBrowser()) return;
  try {
    localStorage.setItem(`${LOCAL_STORAGE_PREFIX}${path}`, JSON.stringify(value));
  } catch (err) {
    console.error('LocalStorage write error:', err);
  }
}

/**
 * Retrieve a leaf value from localStorage by path.
 * @param {string} path - Key path.
 * @returns {any} Parsed value or undefined.
 */
function getLeafFromStorage(path: string): any {
  if (!isBrowser()) return undefined;
  try {
    const item = localStorage.getItem(`${LOCAL_STORAGE_PREFIX}${path}`);
    return item ? JSON.parse(item) : undefined;
  } catch (err) {
    console.error('LocalStorage read error:', err);
    return undefined;
  }
}

/**
 * Provider component for application settings with persistence support.
 * @param {{ children: React.ReactNode }} props
 * @returns JSX.Element
 */
export const SettingsProvider: React.FC<{ children: React.ReactNode }> = ({
  children,
}) => {
  const [settings, setSettings] = useState<Settings>(structuredClone(APP_SETTINGS));

  // Load persisted settings from localStorage on mount
  useEffect(() => {
    if (!isBrowser()) return;

    const init = structuredClone(APP_SETTINGS);
    Object.keys(localStorage)
      .filter((key) => key.startsWith(LOCAL_STORAGE_PREFIX))
      .forEach((key) => {
        const path = key.replace(LOCAL_STORAGE_PREFIX, '');
        const value = getLeafFromStorage(path);
        if (value !== undefined) {
          setToPath(init, path, value);
        }
      });
    setSettings(init);
  }, []); // Run once on mount

  const getOption = useCallback(
    <T,>(path: string): T => {
      return getFromPath(settings, path) as T;
    },
    [settings]
  );

  const setOption = useCallback(<T,>(path: string, value: T) => {
    setSettings((prev) => setToPath({ ...prev }, path, value));
  }, []);

  const storeOption = useCallback(<T,>(path: string, value: T) => {
    setSettings((prev) => {
      const newSettings = setToPath({ ...prev }, path, value);
      storeLeaf(path, value);
      return newSettings;
    });
  }, []);

  const contextValue = useMemo(
    () => ({ getOption, setOption, storeOption, settings }),
    [getOption, setOption, storeOption, settings]
  );

  return (
    <SettingsContext.Provider value={contextValue}>
      {children}
    </SettingsContext.Provider>
  );
};

/**
 * Hook to access application settings context.
 * @returns {SettingsContextType} Context API for settings
 */
export const useSettings = (): SettingsContextType => {
  const ctx = useContext(SettingsContext);
  if (!ctx) {
    throw new Error('useSettings must be used within a SettingsProvider');
  }
  return ctx;
};

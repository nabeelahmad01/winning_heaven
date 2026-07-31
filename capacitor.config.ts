import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.winningheaven.app',
  appName: 'Winning Heaven',
  webDir: 'public',
  appendUserAgent: ' WinningHeavenNative/1.0',
  backgroundColor: '#07131f',
  server: {
    url: process.env.WH_SITE_URL || 'http://127.0.0.1:8000/',
    cleartext: true,
    androidScheme: 'https',
    iosScheme: 'https',
    allowNavigation: ['winningheaven.com', '*.winningheaven.com', '127.0.0.1', 'localhost']
  }
};
export default config;

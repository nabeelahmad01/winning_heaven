import type { CapacitorConfig } from '@capacitor/cli';
const config: CapacitorConfig = {
  appId: 'com.winningheaven.distributor',
  appName: 'Winning Heaven Distributor',
  webDir: 'public',
  appendUserAgent: ' WinningHeavenDistributorNative/1.0',
  backgroundColor: '#07131f',
  server: {
    url: (process.env.WH_SITE_URL || 'http://127.0.0.1:8000') + '/distributor',
    cleartext: true,
    allowNavigation: ['winningheaven.com', '*.winningheaven.com', '127.0.0.1', 'localhost']
  }
};
export default config;

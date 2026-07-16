import { apiRequest } from '@/lib/apiClient'

export interface LoginQrcodeVO {
  scene: string
  qrcode: string
  expiresIn: number
}

export interface LoginStatusVO {
  status: 'pending' | 'success' | 'expired'
  token?: string
}

export interface LoginOauthConfigVO {
  appid: string
  scope: string
  redirectUri: string
  state: string
  authUrl: string
}

const pick = <T = any>(source: any, keys: string[], fallback?: T): T => {
  for (const key of keys) {
    const value = source?.[key]
    if (value !== undefined && value !== null && value !== '') return value as T
  }
  return fallback as T
}

export class AuthApi {
  static async getLoginQrcode(): Promise<LoginQrcodeVO> {
    const data = await apiRequest<any>('user/login/qrcode', { auth: false })
    return {
      scene: String(pick(data, ['scene'], '')),
      qrcode: String(pick(data, ['dataUrl', 'data_url', 'qrcode', 'qrCode', 'qr_code', 'url'], '')),
      expiresIn: Number(pick(data, ['expiresIn', 'expires_in'], 600)) || 600,
    }
  }

  static async getLoginStatus(scene: string): Promise<LoginStatusVO> {
    const data = await apiRequest<any>('user/login/status', {
      params: { scene },
      auth: false,
    })
    return {
      status: String(pick(data, ['status'], 'pending')) as LoginStatusVO['status'],
      token: String(pick(data, ['token'], '')),
    }
  }

  static async getLoginOauthConfig(redirect: string): Promise<LoginOauthConfigVO> {
    const data = await apiRequest<any>('user/login/oauth_config', {
      params: { redirect },
      auth: false,
    })
    return {
      appid: String(pick(data, ['appid'], '')),
      scope: String(pick(data, ['scope'], 'snsapi_login')),
      redirectUri: String(pick(data, ['redirectUri', 'redirect_uri'], '')),
      state: String(pick(data, ['state'], '')),
      authUrl: String(pick(data, ['authUrl', 'auth_url', 'url'], '')),
    }
  }
}
